<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Redemption extends Model {
    protected $guarded=['id'];
    protected function casts(): array { return ['claimed_at'=>'immutable_datetime']; }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function actor() { return $this->belongsTo(User::class,'actor_id'); }
}
