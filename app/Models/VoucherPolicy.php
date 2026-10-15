<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class VoucherPolicy extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['tiers'=>'array']; }
    protected static function booted(): void {
        static::updating(fn () => throw new \LogicException('Buat versi baru; aturan yang sudah tersimpan tidak dapat diubah.'));
        static::deleting(fn () => throw new \LogicException('Versi aturan tidak dapat dihapus.'));
    }
    public static function current(): self { return self::findOrFail(Setting::read('policy_id')); }
}
