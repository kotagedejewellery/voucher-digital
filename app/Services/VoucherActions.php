<?php
namespace App\Services;

use App\Jobs\DeliverMessage;
use App\Models\{Branch,Delivery,PaymentOrder,Redemption,Transfer,User,Voucher};
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\{DB,Queue};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoucherActions
{
    public function approve(PaymentOrder $order, User $actor, array $data): Voucher
    {
        try {
            return DB::transaction(function () use ($order,$actor,$data) {
                $this->activeActor($actor);
                $order=PaymentOrder::with('voucher')->lockForUpdate()->findOrFail($order->id);
                $paid=CarbonImmutable::parse($data['paid_at'],'Asia/Jakarta')->utc();
                $ref=trim($data['merchant_ref']);
                if ($order->status==='PAID') {
                    if ($order->merchant_ref!==$ref || !$order->paid_at->equalTo($paid)) {
                        $this->invalid('payment','Pesanan sudah terverifikasi. Data penerimaan tidak dapat diganti.');
                    }
                    return $order->voucher;
                }
                if ($order->anonymized_at) { $this->invalid('payment','Pesanan sudah diarsipkan. Selesaikan melalui bantuan.'); }
                if ($paid->isFuture()) { $this->invalid('paid_at','Waktu pembayaran tidak boleh di masa depan. Periksa waktu WIB.'); }
                if ((int)$data['amount']!==$order->price) { $this->invalid('amount','Nominal uang masuk harus cocok dengan pesanan.'); }
                $order->update(['status'=>'PAID','merchant_ref'=>$ref,'paid_at'=>$paid,'verified_at'=>now(),'verified_by'=>$actor->id,'correction_reason'=>null]);
                $token=bin2hex(random_bytes(32));
                $voucher=Voucher::create([
                    'payment_order_id'=>$order->id,'code'=>$this->code(),'token_hash'=>hash('sha256',$token),'access_token'=>$token,
                    'owner_name'=>$order->customer_name,'phone'=>$order->phone,'marketing_consent'=>$order->marketing_consent,
                ]);
                $this->audit($actor,'payment.approved','payment',$order->id,['voucher_id'=>$voucher->id,'paid_at'=>$paid->toIso8601String()]);
                $this->meta($voucher,'Purchase',$paid->timestamp,$order->tracking??[], $order->price);
                $this->whatsapp($voucher);
                return $voucher;
            },3);
        } catch (UniqueConstraintViolationException $e) {
            $this->invalid('merchant_ref','Referensi penerimaan sudah dipakai. Gunakan tautan pencarian di bawah untuk memeriksa transaksi tersebut.');
        }
    }

    public function claim(string $code,User $actor,array $data): Redemption
    {
        $ref=mb_strtoupper(trim($data['order_ref']),'UTF-8');
        $digest=hash('sha256',json_encode([$code,$actor->id,(int)$data['branch_id'],$ref,$data['quote']],JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($code,$actor,$data,$ref,$digest) {
                $this->activeActor($actor);
                $voucher=Voucher::with('order.policy')->where('code',$code)->lockForUpdate()->firstOrFail();
                $previous=Redemption::where('request_key',$data['request_key'])->first();
                if ($previous) {
                    abort_unless($previous->actor_id===$actor->id && hash_equals($previous->request_digest,$digest),409,'Request yang sama sudah digunakan untuk data berbeda.');
                    return $previous;
                }
                $quote=$voucher->quote();
                if ($quote['status']!=='AKTIF') { $this->invalid('voucher','Voucher sudah terpakai atau kedaluwarsa.'); }
                if (!hash_equals($quote['key'],$data['quote'])) { $this->invalid('quote','Potongan berubah sejak preview. Muat ulang dan konfirmasi nilai terbaru.'); }
                if ($voucher->anonymized_at) { $this->invalid('voucher','Voucher telah diarsipkan.'); }
                if (!Branch::whereKey($data['branch_id'])->lockForUpdate()->value('active')) { $this->invalid('branch_id','Pilih cabang aktif.'); }
                $claim=Redemption::create([
                    'voucher_id'=>$voucher->id,'branch_id'=>$data['branch_id'],'order_ref'=>$ref,'actor_id'=>$actor->id,
                    'request_key'=>$data['request_key'],'request_digest'=>$digest,'discount'=>$quote['discount'],'age_days'=>$quote['age'],'claimed_at'=>now(),
                ]);
                $voucher->update(['status'=>'TERPAKAI']);
                $this->audit($actor,'voucher.claimed','voucher',$voucher->id,['claim_id'=>$claim->id,'discount'=>$claim->discount,'age'=>$claim->age_days,'eligibility_confirmed'=>true,'order_confirmed'=>true]);
                $context=$voucher->transfer()->exists()?[]:($voucher->order->tracking??[]);
                $this->meta($voucher,'VoucherRedeemed',now()->timestamp,$context);
                return $claim;
            },3);
        } catch (UniqueConstraintViolationException $e) {
            $this->invalid('order_ref','Voucher atau nomor order pada cabang ini sudah diklaim. Periksa riwayat sebelum melanjutkan.');
        }
    }

    public function transfer(string $code,User $actor,array $data): Voucher
    {
        $phone=Phone::normalize($data['phone']);
        return DB::transaction(function () use ($code,$actor,$data,$phone) {
            $this->activeActor($actor);
            $voucher=Voucher::with('order.policy')->where('code',$code)->lockForUpdate()->firstOrFail();
            if ($voucher->quote()['status']!=='AKTIF' || $voucher->transfer()->exists() || $voucher->anonymized_at) {
                $this->invalid('voucher','Transfer hanya sekali untuk voucher aktif dan belum terpakai.');
            }
            Transfer::create(['voucher_id'=>$voucher->id,'actor_id'=>$actor->id,'old_owner'=>['name'=>$voucher->owner_name,'phone'=>$voucher->phone],
                'new_owner'=>['name'=>trim($data['name']),'phone'=>$phone],'approval_note'=>$data['approval_note']]);
            $token=bin2hex(random_bytes(32));
            $voucher->update(['owner_name'=>trim($data['name']),'phone'=>$phone,'code'=>$this->code(),'token_hash'=>hash('sha256',$token),
                'access_token'=>$token,'revision'=>$voucher->revision+1,'marketing_consent'=>false]);
            $this->audit($actor,'voucher.transferred','voucher',$voucher->id,['owner_permission_verified'=>true,'revision'=>$voucher->revision]);
            $this->whatsapp($voucher);
            return $voucher;
        },3);
    }

    public function activeActor(User $actor): void
    {
        abort_unless(User::whereKey($actor->id)->lockForUpdate()->value('active'),403,'Akun tidak aktif.');
    }

    public function code(): string
    {
        $alphabet='23456789ABCDEFGHJKMNPQRSTUVWXYZ'; $code='';
        for ($i=0; $i<10; $i++) { $code.=$alphabet[random_int(0,strlen($alphabet)-1)]; }
        return $code;
    }

    public function audit(User $actor,string $action,string $type,int $id,array $details=[]): void
    {
        DB::table('audit_logs')->insert(['actor_id'=>$actor->id,'action'=>$action,'subject_type'=>$type,'subject_id'=>$id,'details'=>json_encode($details,JSON_THROW_ON_ERROR),'created_at'=>now()]);
    }

    public function meta(Voucher $voucher,string $event,int $time,array $context=[],?int $value=null): void
    {
        $consent=$voucher->marketing_consent && $voucher->phone;
        $user=$consent ? ['ph'=>[hash('sha256',ltrim($voucher->phone,'+'))]] : [];
        foreach (['fbc','fbp','client_ip_address','client_user_agent'] as $key) { if (!empty($context[$key])) { $user[$key]=$context[$key]; } }
        $id=(string)Str::uuid();
        $payload=['event_name'=>$event,'event_time'=>$time,'event_id'=>$id,'action_source'=>$event==='Purchase'?'website':'physical_store','user_data'=>$user];
        if ($event==='Purchase') { $payload['event_source_url']=$context['event_source_url']??url('/'); $payload['custom_data']=['currency'=>'IDR','value'=>$value]; }
        $delivery=Delivery::create(['voucher_id'=>$voucher->id,'kind'=>$event,'event_id'=>$id,'revision'=>$voucher->revision,'payload'=>$consent?$payload:null,
            'status'=>$consent?'PENDING':'SKIPPED','last_error'=>$consent?null:'Tidak ada persetujuan marketing atau identitas yang valid.']);
        if ($consent) { Queue::connection('database')->push(new DeliverMessage($delivery->id)); }
    }

    public function whatsapp(Voucher $voucher,?string $eventId=null): Delivery
    {
        $delivery=Delivery::create(['voucher_id'=>$voucher->id,'kind'=>'WhatsApp','event_id'=>$eventId??(string)Str::uuid(),'revision'=>$voucher->revision,'status'=>'PENDING']);
        // Queue shares the business connection; job insert rolls back with the voucher.
        Queue::connection('database')->push(new DeliverMessage($delivery->id));
        return $delivery;
    }

    private function invalid(string $field,string $message): never
    {
        throw ValidationException::withMessages([$field=>$message]);
    }
}
