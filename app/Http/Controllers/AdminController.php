<?php
namespace App\Http\Controllers;

use App\Models\{Branch,Delivery,PaymentOrder,QrisAsset,Redemption,Setting,Voucher,VoucherPolicy};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard',[
            'counts'=>PaymentOrder::selectRaw("count(*) as checkout, count(*) filter (where status = 'MENUNGGU_VERIFIKASI') as pending, count(*) filter (where status = 'PAID') as paid, coalesce(sum(price) filter (where status = 'PAID'),0) as revenue")->first(),
            'claims'=>Redemption::count(),'viewed'=>Voucher::whereNotNull('viewed_at')->count(),'scanned'=>Voucher::whereNotNull('scanned_at')->count(),
            'qris'=>QrisAsset::find(Setting::read('qris_id')),'branches'=>Branch::where('active',true)->count(),
            'whatsappDeliveryAttention'=>Delivery::needsAttention()->where('kind','WhatsApp')->count(),
            'metaDeliveryAttention'=>Delivery::needsAttention()->where('kind','!=','WhatsApp')->count(),
            'campaigns'=>PaymentOrder::query()->whereNotNull('campaign')->where('campaign','!=','')
                ->leftJoin('vouchers','vouchers.payment_order_id','=','payment_orders.id')
                ->leftJoin('redemptions','redemptions.voucher_id','=','vouchers.id')
                ->selectRaw("campaign, count(*) as checkouts, count(*) filter (where payment_orders.status = 'PAID') as paid, count(redemptions.id) as claims")
                ->groupBy('campaign')->orderByDesc('checkouts')->limit(5)->get(),
        ]);
    }
    public function order(Request $request,PaymentOrder $order)
    {
        $order->load(['qris','policy','voucher.order.policy','voucher.deliveries.resolvedBy','voucher.redemption.branch','voucher.redemption.actor','voucher.transfer']);
        $deliveryIds=$order->voucher?->deliveries->pluck('id')??collect();
        $audit=DB::table('audit_logs as audit')->leftJoin('users as actor','actor.id','=','audit.actor_id')
            ->where(function ($query) use ($order,$deliveryIds) {
                $query->where(fn ($q) => $q->where('audit.subject_type','payment')->where('audit.subject_id',$order->id));
                if ($order->voucher) { $query->orWhere(fn ($q) => $q->where('audit.subject_type','voucher')->where('audit.subject_id',$order->voucher->id)); }
                if ($deliveryIds->isNotEmpty()) { $query->orWhere(fn ($q) => $q->where('audit.subject_type','delivery')->whereIn('audit.subject_id',$deliveryIds)); }
            })->orderByDesc('audit.created_at')->limit(10)
            ->get(['audit.action','audit.created_at','actor.name as actor_name'])
            ->map(fn ($entry) => (object)[...((array)$entry),'at'=>\Carbon\CarbonImmutable::parse($entry->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i'),'label'=>match ($entry->action) {
                'payment.approved'=>'Pembayaran diverifikasi','payment.correction'=>'Perbaikan pembayaran diminta','voucher.claimed'=>'Voucher diklaim',
                'voucher.transferred'=>'Pemilik voucher dipindahkan','delivery.retry'=>'Pengiriman diantrekan ulang','delivery.resolved'=>'Perhatian pengiriman ditutup',
                'whatsapp.resend'=>'WhatsApp diantrekan ulang','delivery.manual_sent'=>'Pengiriman WhatsApp manual dicatat','support.status'=>'Status bantuan diperbarui',
                default=>$entry->action,
            }]);
        if ($request->expectsJson()) {
            return response()->json(['reference'=>$order->reference,'status'=>$order->status,'voucher_code'=>$order->voucher?->code,
                'paid_at'=>$order->paid_at?->toIso8601String(),'verified_at'=>$order->verified_at?->toIso8601String(),
                'deliveries'=>$order->voucher?->deliveries->map(fn($d)=>['id'=>$d->id,'kind'=>$d->kind,'status'=>$d->status,'event_id'=>$d->event_id])??[]]);
        }
        return view('admin.order',['order'=>$order,'audit'=>$audit]);
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
