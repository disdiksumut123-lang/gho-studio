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
        return view('admin.settings', ['gw' => Setting::get('wa_gateway', ''), 'base' => Setting::get('wa_base_url', ''), 'hasToken' => (bool) Setting::get('wa_token')]);
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
