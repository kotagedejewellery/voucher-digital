<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
class Setting extends Model {
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    public static function read(string $key): ?string { return Cache::remember('setting:'.$key, 300, fn () => self::find($key)?->value); }
    public static function put(string $key, string $value): void { self::updateOrCreate(['key'=>$key], ['value'=>$value]); Cache::forget('setting:'.$key); }
}
