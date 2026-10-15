<?php
namespace App\Support;

use Illuminate\Validation\ValidationException;

class Phone
{
    public static function normalize(string $value, string $field = 'phone'): string
    {
        $value=preg_replace('/[\s()\-]/u', '', $value);
        if (str_starts_with($value, '08')) { $value='62'.substr($value,1); }
        $value=ltrim($value,'+');
        if (!preg_match('/^[1-9][0-9]{7,14}$/D', $value)) {
            throw ValidationException::withMessages([$field=>'Isi nomor WhatsApp valid, misalnya 081234567890.']);
        }
        return '+'.$value;
    }
}
