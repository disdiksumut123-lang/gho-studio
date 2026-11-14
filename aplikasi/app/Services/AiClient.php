<?php

namespace App\Services;

use App\Models\AiProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Satu pintu untuk semua penyedia kecerdasan buatan.
 * - openai_compatible: DeepSeek, OpenAI, OpenRouter, Groq, dan sejenisnya
 * - gemini: Google Gemini (REST generateContent)
 * - anthropic: Anthropic Claude (Messages API)
 * Kunci API tidak pernah dikirim ke browser.
 */
class AiClient
{
    public function __construct(private ?AiProvider $provider = null) {}

    public static function default(): self
    {
        $p = AiProvider::where('enabled', true)->orderByDesc('is_default')->orderBy('id')->first();
        return new self($p);
    }

    public function available(): bool { return (bool) $this->provider; }
    public function provider(): ?AiProvider { return $this->provider; }

    /**
     * Kirim permintaan. Jika $json true, hasil di-decode menjadi array.
     * @return array|string
     */
    public function ask(string $system, string $user, bool $json = true, string $feature = 'umum')
    {
        $p = $this->provider ?? throw new RuntimeException(__('Belum ada penyedia yang diaktifkan. Buka Pengaturan › Penyedia.'));
        $lang = app()->getLocale() === 'en' ? 'Answer in English.' : 'Jawab dalam Bahasa Indonesia.';
        $system = trim($system)."\n".$lang.($json ? "\nKembalikan HANYA JSON yang valid, tanpa teks lain dan tanpa blok kode." : '');
        $t0 = microtime(true);
        $in = 0; $out = 0;
        try {
            [$text, $in, $out] = match ($p->kind) {
                'gemini' => $this->gemini($p, $system, $user, $json),
                'anthropic' => $this->anthropic($p, $system, $user),
                default => $this->openai($p, $system, $user, $json),
            };
            $this->log($p, $feature, $in, $out, $t0, true);
        } catch (\Throwable $e) {
            $this->log($p, $feature, $in, $out, $t0, false, $e->getMessage());
            throw new RuntimeException(__('Layanan sedang tidak bisa dihubungi. Jawabanmu tetap tersimpan, silakan coba lagi.').' ('.mb_substr($e->getMessage(), 0, 160).')');
        }
        if (! $json) return trim($text);
        return self::parseJson($text);
    }

    public static function parseJson(string $text): array
    {
        $t = trim($text);
        $t = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $t);
        $start = strpos($t, '{'); $end = strrpos($t, '}');
        if ($start === false || $end === false) throw new RuntimeException('Format jawaban tidak dikenali.');
        $data = json_decode(substr($t, $start, $end - $start + 1), true);
        if (! is_array($data)) throw new RuntimeException('JSON tidak valid.');
        return $data;
    }

    private function http()
    {
        return Http::timeout(90)->connectTimeout(15)->retry(1, 800, throw: false);
    }

    private function openai(AiProvider $p, string $system, string $user, bool $json): array
    {
        $base = rtrim($p->base_url ?: 'https://api.openai.com/v1', '/');
        $body = [
            'model' => $p->model,
            'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
            'max_tokens' => $p->max_tokens,
        ];
        if ($json) $body['response_format'] = ['type' => 'json_object'];
        $r = $this->http()->withToken($p->api_key)->post($base.'/chat/completions', $body);
        if ($r->failed() && $json) { // sebagian model tidak mendukung response_format
            unset($body['response_format']);
            $r = $this->http()->withToken($p->api_key)->post($base.'/chat/completions', $body);
        }
        if ($r->failed()) throw new RuntimeException('HTTP '.$r->status().' '.$r->body());
        return [(string) data_get($r->json(), 'choices.0.message.content', ''), (int) data_get($r->json(), 'usage.prompt_tokens', 0), (int) data_get($r->json(), 'usage.completion_tokens', 0)];
    }

    private function gemini(AiProvider $p, string $system, string $user, bool $json): array
    {
        $base = rtrim($p->base_url ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $body = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
            'generationConfig' => ['maxOutputTokens' => $p->max_tokens] + ($json ? ['responseMimeType' => 'application/json'] : []),
        ];
        $r = $this->http()->withHeaders(['x-goog-api-key' => $p->api_key])->post($base.'/models/'.$p->model.':generateContent', $body);
        if ($r->failed()) throw new RuntimeException('HTTP '.$r->status().' '.$r->body());
        $parts = data_get($r->json(), 'candidates.0.content.parts', []);
        $text = implode('', array_map(fn ($x) => $x['text'] ?? '', $parts));
        return [$text, (int) data_get($r->json(), 'usageMetadata.promptTokenCount', 0), (int) data_get($r->json(), 'usageMetadata.candidatesTokenCount', 0)];
    }

    private function anthropic(AiProvider $p, string $system, string $user): array
    {
        $base = rtrim($p->base_url ?: 'https://api.anthropic.com', '/');
        $r = $this->http()->withHeaders([
            'x-api-key' => $p->api_key,
            'anthropic-version' => '2026-10-21',
        ])->post($base.'/v1/messages', [
            'model' => $p->model ?: 'claude-opus-5-5',
            'max_tokens' => max(1024, (int) $p->max_tokens),
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]],
        ]);
        if ($r->failed()) throw new RuntimeException('HTTP '.$r->status().' '.$r->body());
        $data = $r->json();
        if (($data['stop_reason'] ?? '') === 'refusal') throw new RuntimeException('Permintaan ditolak oleh penyedia.');
        $text = collect($data['content'] ?? [])->where('type', 'text')->pluck('text')->implode('');
        return [$text, (int) data_get($data, 'usage.input_tokens', 0), (int) data_get($data, 'usage.output_tokens', 0)];
    }

    private function log(AiProvider $p, string $feature, int $in, int $out, float $t0, bool $ok, ?string $err = null): void
    {
        try {
            DB::table('ai_usage')->insert([
                'provider_id' => $p->id, 'user_id' => auth()->id(), 'feature' => $feature,
                'tokens_in' => $in, 'tokens_out' => $out, 'latency_ms' => (int) ((microtime(true) - $t0) * 1000),
                'ok' => $ok, 'error' => $err ? mb_substr($err, 0, 250) : null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable) {}
    }
}
