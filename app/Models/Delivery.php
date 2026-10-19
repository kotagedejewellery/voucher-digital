<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class Delivery extends Model {
    protected $guarded=['id'];
    protected $hidden=['payload'];
    protected function casts(): array { return ['payload'=>'encrypted:array','sent_at'=>'immutable_datetime']; }
    public function voucher() { return $this->belongsTo(Voucher::class); }
    public function scopeNeedsAttention(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereIn('status',['FAILED','UNCERTAIN'])
            ->orWhere(fn (Builder $q) => $q->whereIn('status',['PENDING','SENDING'])->where('updated_at','<=',now()->subMinutes(30))));
    }
}
