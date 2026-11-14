<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['key', 'value'];
    private const SECRET = ['wa_token', 'el_key'];

    public static function get(string $k, $default = null)
    {
        $v = static::find($k)?->value;
        if ($v !== null && in_array($k, self::SECRET, true)) { try { $v = Crypt::decryptString($v); } catch (\Throwable) { $v = null; } }
        return $v ?? $default;
    }

    public static function put(string $k, ?string $v): void
    {
        if ($v !== null && in_array($k, self::SECRET, true)) $v = Crypt::encryptString($v);
        static::updateOrCreate(['key' => $k], ['value' => $v]);
    }
}
