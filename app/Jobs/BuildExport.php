<?php
namespace App\Jobs;
use App\Models\{Export,PaymentOrder};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
class BuildExport implements ShouldQueue
{
    use Queueable;
    public int $timeout=60;
    public int $tries=2;
    public function __construct(public int $exportId) {}
    public function handle(): void
    {
        $export=Export::find($this->exportId);
        if (!$export || $export->expires_at->isPast() || $export->status==='READY') { return; }
        $path='exports/'.$export->id.'.csv';
        $export->update(['status'=>'PROCESSING','path'=>$path]);
        Storage::disk('local')->makeDirectory('exports');
        $file=fopen(Storage::disk('local')->path($path),'wb');
        try {
            fwrite($file,"\xEF\xBB\xBF");
            fputcsv($file,['Pesanan','Status','Nama','WhatsApp','Campaign','Pendapatan voucher IDR','Bayar aktual WIB','Kode voucher','Status voucher','Potongan diklaim IDR','Cabang','Order existing','Waktu klaim WIB'],',','"','');
            $filters=$export->filters;
            $query=PaymentOrder::with(['voucher.order.policy','voucher.redemption.branch'])->when($filters['status']??'',fn($q,$s)=>$q->where('status',$s));
            if (!empty($filters['search'])) {
                $term=trim($filters['search']);
                $query->where(fn($q)=>$q->where('reference',$term)->orWhere('merchant_ref',$term)->orWhere('campaign',$term)->orWhereHas('voucher',fn($v)=>$v->where('code',strtoupper($term))));
            }
            $query->chunkById(200,function ($orders) use ($file) {
                foreach ($orders as $o) {
                    $v=$o->voucher; $r=$v?->redemption;
                    $row=[$o->reference,$o->status,$o->customer_name,$o->phone,$o->campaign,$o->status==='PAID'?$o->price:0,
                        $o->paid_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s'),$v?->code,$v?$v->quote()['status']:'',
                        $r?->discount??0,$r?->branch->name,$r?->order_ref,$r?->claimed_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s')];
                    $row=array_map(function ($value) { $s=(string)($value??''); return preg_match('/^[\s\x00-\x1F]*[=+@\-]/u',$s)?"'".$s:$s; },$row);
                    fputcsv($file,$row,',','"','');
                }
            });
        } finally { fclose($file); }
        $current=$export->fresh();
        if (!$current || $current->expires_at->isPast()) { Storage::disk('local')->delete($path); return; }
        $export->update(['status'=>'READY']);
    }
    public function failed(?\Throwable $exception): void { Export::whereKey($this->exportId)->update(['status'=>'FAILED']); }
}
