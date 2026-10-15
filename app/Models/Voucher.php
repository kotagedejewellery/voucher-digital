<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Voucher extends Model {
    protected $guarded = ['id'];
    protected $attributes = ['status'=>'AKTIF','revision'=>1];
    protected $hidden = ['access_token','token_hash','phone','owner_name'];
    protected function casts(): array { return ['access_token'=>'encrypted','owner_name'=>'encrypted','phone'=>'encrypted','marketing_consent'=>'boolean','viewed_at'=>'immutable_datetime','scanned_at'=>'immutable_datetime']; }
    public function order() { return $this->belongsTo(PaymentOrder::class,'payment_order_id'); }
    public function redemption() { return $this->hasOne(Redemption::class); }
    public function transfer() { return $this->hasOne(Transfer::class); }
    public function deliveries() { return $this->hasMany(Delivery::class); }
    public function quote(): array {
        $policy=$this->order->policy;
        $paid=$this->order->paid_at->setTimezone('Asia/Jakarta')->startOfDay();
        $today=now('Asia/Jakarta')->startOfDay();
        $age=(int) $paid->diffInDays($today, false)+1;
        $discount=0;
        $next=null;
        if ($age>=1 && $age<=$policy->valid_days) {
            foreach ($policy->tiers as $index=>$tier) {
                if ($age<=$tier['end']) {
                    $discount=(int)$tier['discount'];
                    if (isset($policy->tiers[$index+1])) {
                        $next=['date'=>$paid->addDays($tier['end']), 'discount'=>(int)$policy->tiers[$index+1]['discount']];
                    }
                    break;
                }
            }
        }
        $status=$this->status==='TERPAKAI' ? 'TERPAKAI' : ($discount ? 'AKTIF' : 'KEDALUWARSA');
        return ['age'=>$age,'discount'=>$discount,'status'=>$status,'next'=>$status==='AKTIF'?$next:null,'key'=>"$age:$discount:$this->revision",
            'expires'=>$paid->addDays($policy->valid_days-1)->endOfDay()];
    }
}
