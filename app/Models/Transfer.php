<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Transfer extends Model {
    protected $guarded=['id'];
    protected function casts(): array { return ['old_owner'=>'encrypted:array','new_owner'=>'encrypted:array','approval_note'=>'encrypted']; }
}
