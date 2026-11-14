<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Kirim otomatis lewat gateway WhatsApp (opsional): Fonnte atau Wablas. */
class WhatsApp
{
    public const GATEWAYS = ['' => 'Tidak dipakai (kirim manual lewat wa.me)', 'fonnte' => 'Fonnte', 'wablas' => 'Wablas'];

    public static function enabled(): bool { return (bool) Setting::get('wa_gateway') && (bool) Setting::get('wa_token'); }

    public static function send(string $phone, string $message): void
    {
        $gw = Setting::get('wa_gateway'); $token = Setting::get('wa_token');
        if (! $gw || ! $token) throw new RuntimeException(__('Gateway WhatsApp belum diatur.'));
        $res = match ($gw) {
            'fonnte' => Http::timeout(20)->withHeaders(['Authorization' => $token])->asForm()->post('https://api.fonnte.com/send', ['target' => $phone, 'message' => $message, 'countryCode' => '62']),
            'wablas' => Http::timeout(20)->withHeaders(['Authorization' => $token])->asForm()->post(rtrim((string) Setting::get('wa_base_url', 'https://jkt.wablas.com'), '/').'/api/send-message', ['phone' => $phone, 'message' => $message]),
            default => throw new RuntimeException('?'),
        };
        $ok = $res->successful() && ($res->json('status') === true || $res->json('status') === 'true' || $res->json('status') === null);
        if (! $ok) throw new RuntimeException(mb_substr($res->json('reason') ?? $res->json('message') ?? $res->body(), 0, 160));
    }
}
