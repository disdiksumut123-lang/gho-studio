<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Services\AiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderController extends Controller
{
    public function index()
    {
        $usage = DB::table('ai_usage')->selectRaw('provider_id, count(*) n, sum(tokens_in) tin, sum(tokens_out) tout, sum(case when ok then 0 else 1 end) fails')
            ->where('created_at', '>=', now()->subDays(30))->groupBy('provider_id')->get()->keyBy('provider_id');
        return view('admin.providers', ['providers' => AiProvider::orderByDesc('is_default')->orderBy('label')->get(), 'presets' => AiProvider::PRESETS, 'usage' => $usage]);
    }

    private function data(Request $r, bool $create): array
    {
        $d = $r->validate([
            'label' => 'required|string|max:80',
            'kind' => 'required|in:openai_compatible,gemini,anthropic',
            'base_url' => 'nullable|url|max:200',
            'model' => 'required|string|max:120',
            'api_key' => ($create ? 'required' : 'nullable').'|string|max:400',
            'max_tokens' => 'nullable|integer|min:256|max:64000',
        ]);
        $d['enabled'] = $r->boolean('enabled', true);
        $d['max_tokens'] = $d['max_tokens'] ?? 4000;
        if (empty($d['api_key'])) unset($d['api_key']);
        return $d;
    }

    public function store(Request $r)
    {
        $p = AiProvider::create($this->data($r, true));
        if (! AiProvider::where('is_default', true)->exists()) $p->update(['is_default' => true]);
        return back()->with('ok', __('Penyedia ditambahkan. Tekan "Uji" untuk memastikan tersambung.'));
    }

    public function update(Request $r, AiProvider $provider)
    {
        $provider->update($this->data($r, false));
        return back()->with('ok', __('Perubahan disimpan.'));
    }

    public function destroy(AiProvider $provider)
    {
        $provider->delete();
        return back()->with('ok', __('Penyedia dihapus.'));
    }

    public function makeDefault(AiProvider $provider)
    {
        AiProvider::query()->update(['is_default' => false]);
        $provider->update(['is_default' => true, 'enabled' => true]);
        return back()->with('ok', __('Penyedia utama diganti ke :p.', ['p' => $provider->label]));
    }

    public function test(AiProvider $provider)
    {
        try {
            $out = (new AiClient($provider))->ask('Kamu asisten uji koneksi.', 'Balas dengan JSON {"ok":true,"pesan":"kalimat sapaan singkat"}', true, 'uji');
            return back()->with('ok', __('Tersambung.').' '.($out['pesan'] ?? ''));
        } catch (\Throwable $e) {
            return back()->with('err', __('Belum tersambung:').' '.$e->getMessage());
        }
    }
}
