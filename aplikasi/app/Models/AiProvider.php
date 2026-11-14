<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $fillable = ['label', 'kind', 'base_url', 'model', 'api_key', 'enabled', 'is_default', 'max_tokens'];
    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return ['api_key' => 'encrypted', 'enabled' => 'boolean', 'is_default' => 'boolean'];
    }

    public function keyHint(): string
    {
        $k = $this->api_key;
        return $k ? '••••'.substr($k, -4) : '-';
    }

    /** Pilihan penyedia yang tampil di formulir. */
    public const PRESETS = [
        'deepseek' => ['label' => 'DeepSeek', 'kind' => 'openai_compatible', 'base_url' => 'https://api.deepseek.com', 'model' => 'deepseek-chat'],
        'openai' => ['label' => 'OpenAI (ChatGPT)', 'kind' => 'openai_compatible', 'base_url' => 'https://api.openai.com/v1', 'model' => 'gpt-4.1-mini'],
        'gemini' => ['label' => 'Google Gemini', 'kind' => 'gemini', 'base_url' => 'https://generativelanguage.googleapis.com/v1beta', 'model' => 'gemini-2.5-flash'],
        'claude' => ['label' => 'Anthropic Claude', 'kind' => 'anthropic', 'base_url' => 'https://api.anthropic.com', 'model' => 'claude-opus-5-5'],
        'openrouter' => ['label' => 'OpenRouter (banyak model)', 'kind' => 'openai_compatible', 'base_url' => 'https://openrouter.ai/api/v1', 'model' => 'deepseek/deepseek-chat'],
        'groq' => ['label' => 'Groq', 'kind' => 'openai_compatible', 'base_url' => 'https://api.groq.com/openai/v1', 'model' => 'llama-3.3-70b-versatile'],
        'lain' => ['label' => 'Lainnya (kompatibel OpenAI)', 'kind' => 'openai_compatible', 'base_url' => '', 'model' => ''],
    ];
}
