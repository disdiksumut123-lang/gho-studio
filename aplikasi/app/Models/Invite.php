<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invite extends Model
{
    protected $fillable = ['training_id', 'cohort_id', 'name', 'nip', 'email', 'whatsapp', 'jabatan', 'unit_kerja', 'user_id', 'token', 'sent_at'];
    protected function casts(): array { return ['sent_at' => 'datetime']; }

    protected static function booted(): void
    {
        static::creating(fn (Invite $i) => $i->token = $i->token ?: Str::lower(Str::random(16)));
    }

    public function cohort() { return $this->belongsTo(Cohort::class); }
    public function training() { return $this->belongsTo(Training::class); }
    public function url(): string { return route('invite.open', $this->token); }

    /** Nomor WA ke format internasional 62xxxx. */
    public static function normalizeWa(?string $n): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $n);
        if ($d === '') return null;
        if (str_starts_with($d, '0')) $d = '62'.substr($d, 1);
        elseif (str_starts_with($d, '8')) $d = '62'.$d;
        return strlen($d) >= 9 ? $d : null;
    }

    public function message(): string
    {
        return __("Yth. :n,\nAnda diundang mengikuti :t di Gho Studio.\nBuka tautan ini untuk mendaftar dan langsung masuk kelas:\n:u", ['n' => $this->name ?: __('Bapak/Ibu'), 't' => $this->training->name, 'u' => $this->url()]);
    }

    public function waLink(): ?string
    {
        return $this->whatsapp ? 'https://wa.me/'.$this->whatsapp.'?text='.rawurlencode($this->message()) : null;
    }
}
