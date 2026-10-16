<?php
namespace App\Http\Controllers;

use App\Models\{Branch,PaymentOrder,QrisAsset,Setting,VoucherPolicy};
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache,DB,Storage};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function landing()
    {
        return view('landing', [
            'policy'=>VoucherPolicy::current(), 'qris'=>QrisAsset::find(Setting::read('qris_id')),
            'branches'=>Cache::remember('branches:public:v1',300,fn()=>Branch::where('active',true)->get(['id','name','address'])->toArray()),
            'support'=>Setting::read('support_phone'),
        ]);
    }

    public function checkout(Request $request)
    {
        $data=$request->validate([
            'name'=>'required|string|max:100','phone'=>'required|string|max:30','service_consent'=>'accepted',
            'marketing_consent'=>'sometimes|boolean','preferred_branch_id'=>['nullable',Rule::exists('branches','id')->where('active',true)],
            'wedding_date'=>'nullable|date_format:Y-m-d|after_or_equal:'.now('Asia/Jakarta')->toDateString(),
            'utm_source'=>'nullable|string|max:150','utm_medium'=>'nullable|string|max:150','utm_campaign'=>'nullable|string|max:150',
            'utm_content'=>'nullable|string|max:150','utm_term'=>'nullable|string|max:150','fbclid'=>'nullable|regex:/^[A-Za-z0-9_\-]{1,512}$/D',
        ]);
        $phone=Phone::normalize($data['phone']);
        $order=DB::transaction(function () use ($data,$phone,$request) {
            $qris=QrisAsset::find(Setting::read('qris_id'));
            if (!$qris) { throw ValidationException::withMessages(['payment'=>'Pembayaran belum tersedia. Hubungi bantuan atau coba lagi nanti.']); }
            $policy=VoucherPolicy::current();
            $token=bin2hex(random_bytes(32));
            $marketing=$request->boolean('marketing_consent');
            $tracking=[];
            if ($marketing) {
                foreach (['_fbc'=>'fbc','_fbp'=>'fbp'] as $cookie=>$key) {
                    $value=$request->cookie($cookie);
                    if (is_string($value) && preg_match('/^fb\.[0-9]+\.[0-9]+\.[A-Za-z0-9_\-]+$/D',$value) && strlen($value)<=600) { $tracking[$key]=$value; }
                }
                if (empty($tracking['fbc']) && !empty($data['fbclid'])) { $tracking['fbc']='fb.1.'.now()->getTimestampMs().'.'.$data['fbclid']; }
                $tracking['client_ip_address']=$request->ip();
                $tracking['client_user_agent']=substr((string)$request->userAgent(),0,500);
                $tracking['event_source_url']=url('/');
            }
            $tracking['utm']=\Illuminate\Support\Arr::only($data,['utm_source','utm_medium','utm_campaign','utm_content','utm_term']);
            return PaymentOrder::create([
                'reference'=>'VS-'.strtoupper(bin2hex(random_bytes(6))), 'token_hash'=>hash('sha256',$token),'access_token'=>$token,
                'customer_name'=>trim($data['name']),'phone'=>$phone,'preferred_branch_id'=>$data['preferred_branch_id']??null,
                'wedding_date'=>$data['wedding_date']??null,'marketing_consent'=>$marketing,'service_consented_at'=>now(),
                'policy_id'=>$policy->id,'qris_asset_id'=>$qris->id,'price'=>$policy->price,'receiver_key'=>$qris->receiver_key,
                'tracking'=>$tracking,'campaign'=>$data['utm_campaign']??null,
            ]);
        });
        $url=route('payment.show',$order->access_token);
        return $request->expectsJson() ? response()->json(['reference'=>$order->reference,'payment_url'=>$url],201) : redirect($url);
    }

    public function show(Request $request, string $token)
    {
        $order=$this->order($token);
        $voucher=$order->voucher;
        $voucherUrl=$voucher && !$voucher->transfer()->exists() ? route('voucher.show',$voucher->access_token) : null;
        if ($request->expectsJson()) {
            return response()->json(['reference'=>$order->reference,'status'=>$order->status,'price'=>$order->price,'voucher_url'=>$voucherUrl]);
        }
        return view('payment',[
            'order'=>$order,
            'voucherUrl'=>$voucherUrl,
            'support'=>Setting::read('support_phone'),
            'qrisAvailable'=>Storage::disk('local')->exists($order->qris->path),
        ]);
    }

    public function confirm(Request $request, string $token)
    {
        $data=$request->validate(['note'=>'nullable|string|max:500']);
        $order=DB::transaction(function () use ($token,$data) {
            $order=PaymentOrder::where('token_hash',hash('sha256',$token))->lockForUpdate()->firstOrFail();
            if ($order->status!=='PAID') {
                $order->update(['status'=>'MENUNGGU_VERIFIKASI','customer_note'=>$data['note']??null,'correction_reason'=>null]);
            }
            return $order;
        });
        return $request->expectsJson() ? response()->json(['status'=>$order->status]) : redirect()->route('payment.show',$token)->with('success','Konfirmasi diterima. Admin/CS akan memeriksa uang masuk.');
    }

    public function qris(Request $request, int $asset)
    {
        $asset=QrisAsset::findOrFail($asset);
        abort_unless(Storage::disk('local')->exists($asset->path),404);
        $headers=['Content-Type'=>$asset->mime,'Cache-Control'=>'public, max-age=31536000, immutable'];
        return $request->boolean('download') ? Storage::disk('local')->download($asset->path,'QRIS-Kotagede.'.($asset->mime==='image/png'?'png':'jpg'),$headers) : Storage::disk('local')->response($asset->path,null,$headers);
    }

    private function order(string $token): PaymentOrder
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/D',$token),404);
        return PaymentOrder::with(['qris','policy','voucher'])->where('token_hash',hash('sha256',$token))->firstOrFail();
    }
}
