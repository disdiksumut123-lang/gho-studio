<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\WhatsApp;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings', ['gw' => Setting::get('wa_gateway', ''), 'base' => Setting::get('wa_base_url', ''), 'hasToken' => (bool) Setting::get('wa_token'),
            'hasEl' => (bool) Setting::get('el_key'), 'elVoice' => Setting::get('el_voice', ''), 'elStt' => Setting::get('el_stt_model', ''), 'elTts' => Setting::get('el_tts_model', ''),
            'quota' => collect(\App\Services\Quota::KEYS)->mapWithKeys(fn ($l, $k) => [$k => \App\Services\Quota::get($k)])->all(), 'usage' => \App\Services\Quota::usage()]);
    }

    public function saveQuota(Request $r)
    {
        foreach (array_keys(\App\Services\Quota::KEYS) as $k) Setting::put($k, (string) max(0, (int) $r->input($k, 0)));
        return back()->with('ok', __('Batas pemakaian disimpan.'));
    }

    public function saveVoice(Request $r)
    {
        $d = $r->validate(['el_key' => 'nullable|string|max:200', 'el_voice' => 'nullable|string|max:60', 'el_stt_model' => 'nullable|string|max:60', 'el_tts_model' => 'nullable|string|max:60', 'remove' => 'nullable']);
        if ($r->boolean('remove')) { Setting::put('el_key', null); return back()->with('ok', __('Kunci ElevenLabs dihapus.')); }
        if (! empty($d['el_key'])) Setting::put('el_key', $d['el_key']);
        foreach (['el_voice', 'el_stt_model', 'el_tts_model'] as $k) Setting::put($k, $d[$k] ?? null);
        return back()->with('ok', __('Pengaturan suara disimpan.'));
    }

    public function save(Request $r)
    {
        $d = $r->validate(['wa_gateway' => 'nullable|in:fonnte,wablas', 'wa_base_url' => 'nullable|url', 'wa_token' => 'nullable|string|max:400', 'test_to' => 'nullable|string|max:20']);
        Setting::put('wa_gateway', $d['wa_gateway'] ?? null);
        Setting::put('wa_base_url', $d['wa_base_url'] ?? null);
        if (! empty($d['wa_token'])) Setting::put('wa_token', $d['wa_token']);
        if (! empty($d['test_to'])) {
            try { WhatsApp::send(\App\Models\Invite::normalizeWa($d['test_to']), __('Uji pesan dari Gho Studio.')); return back()->with('ok', __('Tersimpan. Pesan uji terkirim.')); }
            catch (\Throwable $e) { return back()->with('err', __('Tersimpan, tetapi pesan uji gagal:').' '.$e->getMessage()); }
        }
        return back()->with('ok', __('Pengaturan WhatsApp disimpan.'));
    }
}
