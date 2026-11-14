<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Pembatas pemakaian agar kuota API tidak cepat habis. Angka 0 atau kosong berarti tanpa batas. */
class Quota
{
    public const KEYS = [
        'q_ai_month' => 'Batas permintaan layanan cerdas per bulan (seluruh aplikasi)',
        'q_ai_user_day' => 'Batas permintaan layanan cerdas per peserta per hari',
        'q_voice_month' => 'Batas ubah suara per bulan (seluruh aplikasi)',
        'q_voice_user_day' => 'Batas ubah suara per peserta per hari',
        'q_voice_seconds' => 'Lama rekaman suara maksimal (detik) bawaan',
    ];

    public static function get(string $k): int { return (int) Setting::get($k, $k === 'q_voice_seconds' ? 60 : 0); }

    private static function count(bool $voice, bool $perUser, bool $month): int
    {
        $q = DB::table('ai_usage')->where('created_at', '>=', $month ? now()->startOfMonth() : now()->startOfDay());
        $voice ? $q->where('feature', 'like', 'suara%') : $q->where('feature', 'not like', 'suara%');
        if ($perUser) $q->where('user_id', auth()->id());
        return $q->count();
    }

    public static function usage(): array
    {
        return ['ai_month' => self::count(false, false, true), 'voice_month' => self::count(true, false, true)];
    }

    /** Lempar galat bila batas tercapai. Staf (admin/fasilitator/coach) hanya kena batas bulanan. */
    public static function check(bool $voice): void
    {
        $p = $voice ? 'q_voice' : 'q_ai';
        if (($m = self::get($p.'_month')) && self::count($voice, false, true) >= $m)
            throw new RuntimeException(__('Kuota bulan ini sudah habis. Hubungi admin.'));
        $u = auth()->user();
        $staff = $u && collect(['admin', 'fasilitator', 'coach'])->contains(fn ($r) => in_array($r, $u->roles ?? [], true));
        if (! $staff && ($d = self::get($p.'_user_day')) && self::count($voice, true, false) >= $d)
            throw new RuntimeException(__('Batas pemakaian harian sudah tercapai. Coba lagi besok.'));
    }

    public static function logVoice(string $feature, int $seconds = 0): void
    {
        DB::table('ai_usage')->insert(['provider_id' => null, 'user_id' => auth()->id(), 'feature' => $feature, 'tokens_in' => $seconds, 'tokens_out' => 0, 'latency_ms' => 0, 'ok' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
