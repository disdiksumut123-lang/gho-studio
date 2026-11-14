<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Suara lewat ElevenLabs: ucapan → teks (Scribe) dan teks → suara. Tanpa kunci, browser memakai Web Speech API. */
class Voice
{
    private const BASE = 'https://api.elevenlabs.io/v1';

    public static function enabled(): bool { return (bool) Setting::get('el_key'); }

    public static function transcribe(string $path, string $mime): string
    {
        $key = Setting::get('el_key') ?: throw new RuntimeException('no_key');
        $lang = app()->getLocale() === 'en' ? 'eng' : 'ind';
        $res = Http::timeout(60)->withHeaders(['xi-api-key' => $key])
            ->attach('file', fopen($path, 'r'), 'suara.'.(str_contains($mime, 'mp4') ? 'mp4' : (str_contains($mime, 'ogg') ? 'ogg' : 'webm')), ['Content-Type' => $mime])
            ->post(self::BASE.'/speech-to-text', ['model_id' => Setting::get('el_stt_model') ?: 'scribe_v1', 'language_code' => $lang, 'tag_audio_events' => 'false']);
        if (! $res->successful()) throw new RuntimeException(mb_substr($res->json('detail.message') ?? $res->body(), 0, 200));
        return trim((string) $res->json('text'));
    }

    /** Mengembalikan jalur berkas mp3 (disimpan agar teks yang sama tidak dibayar dua kali). */
    public static function speak(string $text): string
    {
        $key = Setting::get('el_key') ?: throw new RuntimeException('no_key');
        $voice = Setting::get('el_voice') ?: 'JBFqnCBsd6RMkjVDRZzb';
        $model = Setting::get('el_tts_model') ?: 'eleven_multilingual_v2';
        $text = mb_substr(trim($text), 0, 5000);
        $file = 'suara/'.sha1($voice.$model.$text).'.mp3';
        if (Storage::disk('local')->exists($file)) return $file;
        $res = Http::timeout(120)->withHeaders(['xi-api-key' => $key, 'Accept' => 'audio/mpeg'])
            ->post(self::BASE.'/text-to-speech/'.$voice, ['text' => $text, 'model_id' => $model]);
        if (! $res->successful()) throw new RuntimeException(mb_substr($res->body(), 0, 200));
        Storage::disk('local')->put($file, $res->body());
        return $file;
    }
}
