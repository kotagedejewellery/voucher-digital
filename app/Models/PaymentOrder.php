<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PaymentOrder extends Model {
    protected $guarded = ['id'];
    protected $attributes = ['status'=>'MENUNGGU_PEMBAYARAN'];
    protected $hidden = ['access_token','token_hash','tracking','phone','customer_name','merchant_ref'];
    protected function casts(): array { return [
        'customer_name'=>'encrypted','phone'=>'encrypted','access_token'=>'encrypted','customer_note'=>'encrypted','correction_reason'=>'encrypted',
        'tracking'=>'encrypted:array','marketing_consent'=>'boolean','support_open'=>'boolean','paid_at'=>'immutable_datetime',
        'verified_at'=>'immutable_datetime','anonymized_at'=>'immutable_datetime','wedding_date'=>'date',
    ]; }
    public function voucher() { return $this->hasOne(Voucher::class); }
    public function policy() { return $this->belongsTo(VoucherPolicy::class); }
    public function qris() { return $this->belongsTo(QrisAsset::class,'qris_asset_id'); }
}
