<?php
namespace App\Http\Controllers;
use App\Jobs\DeliverMessage;
use App\Models\{Delivery,PaymentOrder,Voucher};
use App\Services\VoucherActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Queue};
class DeliveryController extends Controller
{
    public function retry(Request $request,Delivery $delivery,VoucherActions $actions)
    {
        DB::transaction(function () use ($request,$delivery,$actions) {
            $actions->activeActor($request->user());
            $voucher=Voucher::lockForUpdate()->findOrFail($delivery->voucher_id);
            $delivery=Delivery::lockForUpdate()->findOrFail($delivery->id);
            abort_unless($delivery->status==='FAILED',422,'Hanya pengiriman gagal yang dapat diulang.');
            if ($delivery->kind==='WhatsApp') {
                abort_unless(config('voucher.waco.ready'),422,'Konfigurasi WACO belum lengkap atau URL aplikasi belum HTTPS. Gunakan WhatsApp manual.');
                abort_if($voucher->anonymized_at || $delivery->revision!==$voucher->revision,422,'Link lama tidak dapat dikirim ulang. Buka voucher pemilik saat ini.');
                $request->validate(['send_confirmed'=>'accepted']);
            }
            $delivery->update(['status'=>'PENDING','last_error'=>null]);
            Queue::connection('database')->push(new DeliverMessage($delivery->id));
            $actions->audit($request->user(),'delivery.retry','delivery',$delivery->id);
        });
        return redirect()->route('admin.order',$delivery->voucher->order->reference)->with('success','Pengiriman diantrekan ulang dengan ID/waktu kejadian yang sama.');
    }
    public function resend(Request $request,Voucher $voucher,VoucherActions $actions)
    {
        $data=$request->validate(['request_key'=>'required|uuid','send_confirmed'=>'accepted']);
        DB::transaction(function () use ($request,$voucher,$actions,$data) {
            $actions->activeActor($request->user());
            $voucher=Voucher::lockForUpdate()->findOrFail($voucher->id);
            abort_unless(config('voucher.waco.ready'),422,'Konfigurasi WACO belum lengkap atau URL aplikasi belum HTTPS. Gunakan WhatsApp manual.');
            abort_if($voucher->anonymized_at || !$voucher->phone,422,'Data pemilik tidak tersedia.');
            $previous=Delivery::where('event_id',$data['request_key'])->first();
            if ($previous) {
                abort_unless($previous->voucher_id===$voucher->id && $previous->kind==='WhatsApp' && $previous->revision===$voucher->revision,409,'Request sudah digunakan untuk voucher atau pemilik berbeda.');
                return;
            }
            abort_if($voucher->deliveries()->where('kind','WhatsApp')->where('revision',$voucher->revision)->whereIn('status',['PENDING','SENDING'])->exists(),422,'Pengiriman masih diproses. Periksa status sebelum mengirim ulang.');
            $delivery=$actions->whatsapp($voucher,$data['request_key']);
            $actions->audit($request->user(),'whatsapp.resend','delivery',$delivery->id,['history_checked'=>true]);
        });
        return redirect()->route('admin.voucher',['code'=>$voucher->fresh()->code])->with('success','Permintaan pengiriman dicatat. Periksa hasil pada detail pesanan.');
    }
    public function markManual(Request $request,Voucher $voucher,VoucherActions $actions)
    {
        $request->validate(['send_confirmed'=>'accepted']);
        DB::transaction(function () use ($request,$voucher,$actions) {
            $actions->activeActor($request->user());
            abort_if(config('voucher.waco.ready'),422,'WACO sudah aktif. Gunakan pengiriman otomatis atau kirim ulang WACO.');
            $voucher=Voucher::lockForUpdate()->findOrFail($voucher->id);
            abort_if($voucher->anonymized_at || !$voucher->phone,422,'Data pemilik tidak tersedia.');
            $delivery=Delivery::lockForUpdate()->where('voucher_id',$voucher->id)->where('kind','WhatsApp')
                ->where('revision',$voucher->revision)->whereIn('status',['PENDING','SKIPPED'])->latest('id')->first();
            abort_unless($delivery,422,'Pengiriman manual tidak lagi diperlukan untuk voucher ini.');
            $delivery->update(['status'=>'MANUAL_SENT','sent_at'=>now(),'last_error'=>'Link dikirim manual oleh operator.']);
            $actions->audit($request->user(),'delivery.manual_sent','delivery',$delivery->id);
        });
        return redirect()->route('admin.voucher',['code'=>$voucher->fresh()->code])->with('success','Pengiriman WhatsApp manual dicatat. Status ini tidak membuktikan pesan telah dibaca pelanggan.');
    }
    public function support(Request $request,PaymentOrder $order,VoucherActions $actions)
    {
        $data=$request->validate(['support_open'=>'required|boolean']);
        DB::transaction(function () use ($request,$order,$actions,$data) {
            $actions->activeActor($request->user());
            PaymentOrder::whereKey($order->id)->update(['support_open'=>$data['support_open']]);
            $actions->audit($request->user(),'support.status','payment',$order->id,['open'=>(bool)$data['support_open']]);
        });
        return redirect()->route('admin.order',$order->reference)->with('success','Status bantuan diperbarui.');
    }
}
