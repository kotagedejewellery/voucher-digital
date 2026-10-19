<?php
namespace App\Http\Controllers;

use App\Models\{Branch,Delivery,PaymentOrder,QrisAsset,Redemption,Setting,Voucher,VoucherPolicy};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard',[
            'counts'=>PaymentOrder::selectRaw("count(*) as checkout, count(*) filter (where status = 'MENUNGGU_VERIFIKASI') as pending, count(*) filter (where status = 'PAID') as paid, coalesce(sum(price) filter (where status = 'PAID'),0) as revenue")->first(),
            'claims'=>Redemption::count(),'viewed'=>Voucher::whereNotNull('viewed_at')->count(),'scanned'=>Voucher::whereNotNull('scanned_at')->count(),
            'qris'=>QrisAsset::find(Setting::read('qris_id')),'branches'=>Branch::where('active',true)->count(),
            'deliveryAttention'=>Delivery::needsAttention()->count(),
        ]);
    }
    public function order(Request $request,PaymentOrder $order)
    {
        $order->load(['qris','policy','voucher.order.policy','voucher.deliveries','voucher.redemption.branch','voucher.redemption.actor','voucher.transfer']);
        if ($request->expectsJson()) {
            return response()->json(['reference'=>$order->reference,'status'=>$order->status,'voucher_code'=>$order->voucher?->code,
                'paid_at'=>$order->paid_at?->toIso8601String(),'verified_at'=>$order->verified_at?->toIso8601String(),
                'deliveries'=>$order->voucher?->deliveries->map(fn($d)=>['id'=>$d->id,'kind'=>$d->kind,'status'=>$d->status,'event_id'=>$d->event_id])??[]]);
        }
        return view('admin.order',['order'=>$order]);
    }
    public function voucher(Request $request)
    {
        $data=$request->validate([
            'code'=>'required|string|max:300', 'from_history'=>'nullable|boolean',
            'history_search'=>'nullable|string|max:150', 'history_lifecycle'=>'nullable|in:,AKTIF,TERPAKAI,KEDALUWARSA,PERLU_DIPERIKSA',
            'history_paid_from'=>'nullable|date_format:Y-m-d|before_or_equal:history_paid_to',
            'history_paid_to'=>'nullable|date_format:Y-m-d|after_or_equal:history_paid_from',
        ]);
        $input=trim($data['code']);
        if (preg_match('/^[a-f0-9]{64}$/D',$input)) { $hash=hash('sha256',$input); }
        elseif (preg_match('~/v/([a-f0-9]{64})$~D',$input,$match)) { $hash=hash('sha256',$match[1]); }
        else { $code=strtoupper($input); }
        $voucher=Voucher::with(['order.policy','redemption.branch','redemption.actor','transfer','deliveries'])->when(isset($hash),fn($q)=>$q->where('token_hash',$hash),fn($q)=>$q->where('code',$code??''))->first();
        if (!$voucher) { return redirect()->route('admin.orders')->withErrors(['code'=>'Kode atau link voucher tidak ditemukan. Periksa kembali.'])->withInput(); }
        if (!$voucher->scanned_at) { $voucher->update(['scanned_at'=>now()]); }
        $quote=$voucher->quote();
        if ($request->expectsJson()) { return response()->json(['code'=>$voucher->code,'status'=>$quote['status'],'age'=>$quote['age'],'discount'=>$quote['discount'],'quote'=>$quote['key']]); }
        $fromHistory=$request->boolean('from_history');
        $backUrl=$fromHistory ? route('admin.voucher-history',array_filter([
            'search'=>$data['history_search']??'', 'lifecycle'=>$data['history_lifecycle']??'',
            'paid_from'=>$data['history_paid_from']??'', 'paid_to'=>$data['history_paid_to']??'',
        ],fn ($value) => $value!=='')) : route('admin.order',$voucher->order->reference);
        return view('admin.voucher',['voucher'=>$voucher,'quote'=>$quote,'branches'=>Branch::where('active',true)->get(['id','name']), 'requestKey'=>old('request_key',(string)Str::uuid()),'waRequestKey'=>old('request_key',(string)Str::uuid()),'backUrl'=>$backUrl,'backLabel'=>$fromHistory?'Riwayat Voucher':'Detail pesanan '.$voucher->order->reference]);
    }
    public function settings()
    {
        return view('admin.settings',['qris'=>QrisAsset::find(Setting::read('qris_id')),'branches'=>Branch::orderBy('id')->get(),
            'policy'=>VoucherPolicy::current(),'support'=>Setting::read('support_phone'),'users'=>\App\Models\User::orderBy('name')->get(['id','name','email','active'])]);
    }
}
