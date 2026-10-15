<?php
namespace App\Http\Controllers;
use App\Services\VoucherActions;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function transfer(Request $request,string $code,VoucherActions $actions)
    {
        $data=$request->validate(['name'=>'required|string|max:100|not_regex:/^\s*$/u','phone'=>'required|string|max:30','owner_approved'=>'accepted','approval_note'=>'required|string|max:500']);
        $voucher=$actions->transfer($code,$request->user(),$data);
        return $request->expectsJson() ? response()->json(['code'=>$voucher->code,'voucher_url'=>route('voucher.show',$voucher->access_token)]) : redirect()->route('admin.voucher',['code'=>$voucher->code])->with('success','Pemilik diperbarui. Link dan kode lama dicabut; tanggal pembelian tetap.');
    }

    public function claim(Request $request,string $code,VoucherActions $actions)
    {
        $data=$request->validate([
            'branch_id'=>'required|integer|min:1','order_ref'=>'required|string|max:100|not_regex:/^\s*$/u',
            'request_key'=>'required|uuid','quote'=>'required|string|max:80','eligibility_confirmed'=>'accepted','order_confirmed'=>'accepted',
        ]);
        $claim=$actions->claim($code,$request->user(),$data);
        return $request->expectsJson() ? response()->json(['claim_id'=>$claim->id,'discount'=>$claim->discount,'order_ref'=>$claim->order_ref]) : redirect()->route('admin.voucher',['code'=>$code])->with('success','Klaim berhasil. Masukkan potongan terkunci ke sistem order pada referensi yang sama.');
    }
}
