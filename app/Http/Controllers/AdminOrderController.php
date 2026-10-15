<?php
namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Services\VoucherActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    public function approve(Request $request,PaymentOrder $order,VoucherActions $actions)
    {
        $data=$request->validate([
            'merchant_ref'=>'required|string|max:150|not_regex:/^\s*$/u',
            'paid_at'=>['required','date','regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/D'],
            'amount'=>'required|integer|min:1','money_confirmed'=>'accepted',
        ]);
        $voucher=$actions->approve($order,$request->user(),$data);
        return $request->expectsJson() ? response()->json(['code'=>$voucher->code,'voucher_url'=>route('voucher.show',$voucher->access_token)]) : redirect()->route('admin.order',$order->reference)->with('success','Uang masuk terverifikasi. Voucher sudah diterbitkan.');
    }

    public function correct(Request $request,PaymentOrder $order,VoucherActions $actions)
    {
        $data=$request->validate(['reason'=>'required|string|max:500']);
        DB::transaction(function () use ($order,$request,$data,$actions) {
            $actions->activeActor($request->user());
            $order=PaymentOrder::lockForUpdate()->findOrFail($order->id);
            abort_if($order->status==='PAID',409,'Pembayaran sudah diverifikasi.');
            $order->update(['status'=>'PERLU_PERBAIKAN','correction_reason'=>$data['reason']]);
            $actions->audit($request->user(),'payment.correction','payment',$order->id);
        });
        return $request->expectsJson() ? response()->json(['status'=>'PERLU_PERBAIKAN']) : redirect()->route('admin.order',$order->reference)->with('success','Permintaan perbaikan disimpan.');
    }
}
