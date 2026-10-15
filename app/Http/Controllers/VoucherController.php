<?php
namespace App\Http\Controllers;

use App\Models\{Setting,Voucher};
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function show(Request $request,string $token)
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/D',$token),404);
        $voucher=Voucher::with(['order.policy','redemption.branch','transfer'])->where('token_hash',hash('sha256',$token))->firstOrFail();
        abort_if($voucher->anonymized_at,410,'Voucher telah diarsipkan. Hubungi bantuan.');
        if (!$voucher->viewed_at) { $voucher->update(['viewed_at'=>now()]); }
        $quote=$voucher->quote();
        if ($request->expectsJson()) { return response()->json(['code'=>$voucher->code,'status'=>$quote['status'],'age'=>$quote['age'],'discount'=>$quote['discount'],'quote'=>$quote['key']]); }
        $qr=(new Writer(new ImageRenderer(new RendererStyle(280,4),new SvgImageBackEnd())))->writeString(route('voucher.show',$token));
        return view('voucher',['voucher'=>$voucher,'quote'=>$quote,'qr'=>$qr,'support'=>Setting::read('support_phone')]);
    }
}
