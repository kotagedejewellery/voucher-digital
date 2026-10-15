<?php
namespace App\Jobs;

use App\Models\{Delivery,Voucher};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\{DB,Http};

class DeliverMessage implements ShouldQueue
{
    use Queueable;
    public int $tries=4;
    public int $timeout=45;
    public function __construct(public int $deliveryId) {}
    public function backoff(): array { return [30,120,600]; }

    public function handle(): void
    {
        if (Delivery::whereKey($this->deliveryId)->value('kind')==='WhatsApp') { $this->whatsapp(); return; }
        DB::transaction(function () {
            $delivery=Delivery::with('voucher')->lockForUpdate()->find($this->deliveryId);
            if (!$delivery || in_array($delivery->status,['SENT','SKIPPED'],true)) { return; }
            $config=config('voucher.meta');
            if (!$config['dataset_id'] || !$config['token']) {
                $delivery->update(['status'=>'FAILED','last_error'=>'Credentials Meta belum diatur. Atur environment lalu ulangi pengiriman.']);
                return;
            }
            if (!$delivery->payload) { $delivery->update(['status'=>'SKIPPED','last_error'=>'Data matching tidak tersedia.']); return; }
            $body=['data'=>[$delivery->payload]];
            if ($config['test_event_code']) { $body['test_event_code']=$config['test_event_code']; }
            // Keep the event ID and actual event time on retries, including after a lost response.
            $response=Http::withToken($config['token'])->connectTimeout(5)->timeout(15)->post('https://graph.facebook.com/'.$config['version'].'/'.$config['dataset_id'].'/events',$body);
            if (!$response->successful() || (int)$response->json('events_received')<1) {
                if ($response->status()===429 || $response->serverError()) { throw new \RuntimeException('Meta sementara tidak tersedia.'); }
                $delivery->update(['status'=>'FAILED','last_error'=>'Meta menolak atau belum menerima event. Periksa Events Manager; waktu kejadian tidak diganti.']);
                return;
            }
            $delivery->update(['status'=>'SENT','sent_at'=>now(),'last_error'=>null]);
        });
    }

    public function failed(?\Throwable $exception): void
    {
        Delivery::whereKey($this->deliveryId)->where('status','PENDING')->update(['status'=>'FAILED','last_error'=>'Pengiriman gagal. Periksa koneksi dan ulangi pengiriman.']);
        Delivery::whereKey($this->deliveryId)->where('kind','WhatsApp')->where('status','SENDING')->update(['status'=>'UNCERTAIN','last_error'=>'Hasil pengiriman belum pasti. Periksa riwayat WACO sebelum mengirim ulang.']);
    }

    private function whatsapp(): void
    {
        $candidate=Delivery::find($this->deliveryId);
        if (!$candidate) { return; }
        $send=DB::transaction(function () use ($candidate) {
            $voucher=Voucher::lockForUpdate()->findOrFail($candidate->voucher_id);
            $delivery=Delivery::lockForUpdate()->find($this->deliveryId);
            if (!$delivery) { return false; }
            if ($delivery->status==='SENDING') {
                $delivery->update(['status'=>'UNCERTAIN','last_error'=>'Hasil pengiriman belum pasti. Periksa riwayat WACO sebelum mengirim ulang.']);
                return false;
            }
            if ($delivery->status!=='PENDING') { return false; }
            if ($delivery->revision!==$voucher->revision || $voucher->anonymized_at || !$voucher->phone) {
                $delivery->update(['status'=>'SKIPPED','last_error'=>'Link dicabut atau data pemilik tidak tersedia.']);
                return false;
            }
            if (!config('voucher.waco.ready')) {
                $delivery->update(['status'=>'SKIPPED','last_error'=>'WACO belum dikonfigurasi lengkap. Siapkan template, nomor pengirim, credentials dan URL HTTPS; sementara gunakan WhatsApp manual.']);
                return false;
            }
            // Persist before I/O: a crash or lost acknowledgement must never cause a blind duplicate POST.
            $delivery->update(['status'=>'SENDING','last_error'=>null]);
            return true;
        });
        if (!$send) { return; }
        $retry=DB::transaction(function () use ($candidate) {
            // Same lock order as transfer/pruning; keep the current token valid throughout the send.
            $voucher=Voucher::lockForUpdate()->findOrFail($candidate->voucher_id);
            $delivery=Delivery::lockForUpdate()->find($this->deliveryId);
            if (!$delivery || $delivery->status!=='SENDING') { return false; }
            if ($delivery->revision!==$voucher->revision || $voucher->anonymized_at || !$voucher->phone) {
                $delivery->update(['status'=>'SKIPPED','last_error'=>'Link dicabut atau data pemilik tidak tersedia.']);
                return false;
            }
            $config=config('voucher.waco');
            $body=['nomor'=>ltrim($voucher->phone,'+'),'nama'=>$voucher->owner_name,'dari'=>ltrim($config['sender'],'+'),
                'template'=>$config['template'],'isi'=>[$voucher->owner_name,$voucher->code,route('voucher.show',$voucher->access_token)]];
            try {
                $response=Http::withToken($config['token'])->acceptJson()->withOptions(['allow_redirects'=>false])->connectTimeout(5)->timeout(15)
                    ->post('https://waco.id/api/v1/kirim',$body);
            } catch (ConnectionException $e) {
                $delivery->update(['status'=>'UNCERTAIN','last_error'=>'Respons WACO tidak diterima. Pesan mungkin sudah terkirim; periksa riwayat sebelum mengirim ulang.']);
                return false;
            }
            if ($response->status()===200 && $response->json('status')==='terkirim') {
                $delivery->update(['status'=>'SENT','sent_at'=>now(),'last_error'=>null,'payload'=>['message_id'=>$response->json('message_id')]]);
                return false;
            }
            if ($response->status()===429) {
                $delivery->update(['status'=>'PENDING','last_error'=>'Batas pengiriman WACO tercapai. Antrean akan mencoba kembali.']);
                return true;
            }
            $rejected=in_array($response->status(),[400,401,403,404,409,422,502],true);
            $delivery->update(['status'=>$rejected?'FAILED':'UNCERTAIN','last_error'=>$rejected?
                'WACO menolak pesan (HTTP '.$response->status().'). Periksa akun, template, nomor dan riwayat WACO sebelum mengulang.':
                'Hasil WACO belum pasti. Periksa riwayat WACO sebelum mengirim ulang.']);
            return false;
        });
        if ($retry) { $this->release([60,120,600][min(max($this->attempts()-1,0),2)]); }
    }
}
