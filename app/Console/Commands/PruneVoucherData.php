<?php
namespace App\Console\Commands;
use App\Models\{Delivery,Export,PaymentOrder,Transfer,Voucher};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB,Storage};
class PruneVoucherData extends Command
{
    protected $signature='voucher:prune';
    protected $description='Anonimkan data yang melewati masa retensi; kasus bantuan terbuka dikecualikan.';
    public function handle(): int
    {
        $count=0;
        PaymentOrder::whereNull('anonymized_at')->where('support_open',false)->where(fn($q)=>$q->where('status','PAID')->orWhere('updated_at','<',now()->subDays(90)))->chunkById(200,function ($orders) use (&$count) {
            foreach ($orders as $candidate) {
                DB::transaction(function () use ($candidate,&$count) {
                    $order=PaymentOrder::lockForUpdate()->findOrFail($candidate->id);
                    if ($order->support_open || $order->anonymized_at) { return; }
                    $voucher=Voucher::with(['order.policy','redemption'])->where('payment_order_id',$order->id)->lockForUpdate()->first();
                    if ($voucher) {
                        $retentionFrom=$voucher->redemption?->claimed_at??$voucher->quote()['expires'];
                        if ($retentionFrom->addDays(90)->isFuture()) { return; }
                        $token=bin2hex(random_bytes(32));
                        $voucher->update(['owner_name'=>null,'phone'=>null,'marketing_consent'=>false,'access_token'=>$token,'token_hash'=>hash('sha256',$token),'anonymized_at'=>now(),'revision'=>$voucher->revision+1]);
                        Transfer::where('voucher_id',$voucher->id)->update(['old_owner'=>null,'new_owner'=>null,'approval_note'=>null]);
                        Delivery::where('voucher_id',$voucher->id)->update(['payload'=>null]);
                        Delivery::where('voucher_id',$voucher->id)->whereIn('status',['PENDING','SENDING','FAILED','UNCERTAIN'])->update(['status'=>'SKIPPED','last_error'=>'Data telah dianonimkan.']);
                    } elseif ($order->status==='PAID' || $order->updated_at->addDays(90)->isFuture()) { return; }
                    $token=bin2hex(random_bytes(32));
                    $order->update(['customer_name'=>null,'phone'=>null,'wedding_date'=>null,'customer_note'=>null,'correction_reason'=>null,'tracking'=>null,
                        'campaign'=>null,'marketing_consent'=>false,'access_token'=>$token,'token_hash'=>hash('sha256',$token),'anonymized_at'=>now()]);
                    $count++;
                },3);
            }
        });
        Export::where('expires_at','<=',now())->chunkById(100,function ($exports) {
            foreach ($exports as $export) { if ($export->path) { Storage::disk('local')->delete($export->path); } $export->delete(); }
        });
        $this->info($count.' pesanan dianonimkan.'); return self::SUCCESS;
    }
}
