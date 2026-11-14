@extends('layouts.app')
@section('title', 'WhatsApp')
@section('content')
<form method="post" action="{{ route('admin.settings.quota') }}" class="card stack" style="max-width:560px">@csrf
  <h2>{{ __('Batas pemakaian (hemat kuota)') }}</h2>
  <div class="grid g2"><div class="card stat"><span class="label">{{ __('Layanan cerdas bulan ini') }}</span><div class="n mono">{{ $usage['ai_month'] }}@if ($quota['q_ai_month'])<span class="faint" style="font-size:14px"> / {{ $quota['q_ai_month'] }}</span>@endif</div>
      @if ($quota['q_ai_month'])<div class="bar"><span style="width:{{ min(100, $usage['ai_month'] / max(1, $quota['q_ai_month']) * 100) }}%"></span></div>@endif</div>
    <div class="card stat"><span class="label">{{ __('Suara bulan ini') }}</span><div class="n mono">{{ $usage['voice_month'] }}@if ($quota['q_voice_month'])<span class="faint" style="font-size:14px"> / {{ $quota['q_voice_month'] }}</span>@endif</div>
      @if ($quota['q_voice_month'])<div class="bar"><span style="width:{{ min(100, $usage['voice_month'] / max(1, $quota['q_voice_month']) * 100) }}%"></span></div>@endif</div></div>
  @foreach (\App\Services\Quota::KEYS as $k => $l)<div class="field"><label>{{ __($l) }}</label><input type="number" min="0" name="{{ $k }}" value="{{ $quota[$k] }}"></div>@endforeach
  <p class="faint" style="font-size:12px">{{ __('Isi 0 untuk tanpa batas. Batas per peserta tidak berlaku untuk fasilitator dan coach. Batas per aktivitas (jumlah karakter, lama rekaman, boleh tempel teks) diatur di tiap aktivitas.') }}</p>
  <div><button class="btn primary">{{ __('Simpan') }}</button></div>
</form>
<div class="head"><div><h1>{{ __('WhatsApp dan suara') }}</h1><p class="muted">{{ __('Tanpa gateway, tombol WA membuka WhatsApp dengan pesan undangan siap kirim. Dengan gateway, undangan terkirim otomatis sekaligus.') }}</p></div></div>
<form method="post" action="{{ route('admin.settings.save') }}" class="card stack" style="max-width:560px">@csrf
  <div class="field"><label>{{ __('Gateway') }}</label><select name="wa_gateway">@foreach (\App\Services\WhatsApp::GATEWAYS as $k => $v)<option value="{{ $k }}" @selected($gw === $k)>{{ __($v) }}</option>@endforeach</select></div>
  <div class="field"><label>{{ __('Alamat server (khusus Wablas)') }}</label><input type="url" name="wa_base_url" value="{{ $base }}" placeholder="https://jkt.wablas.com"></div>
  <div class="field"><label>{{ __('Token') }}</label><input type="password" name="wa_token" autocomplete="off" placeholder="{{ $hasToken ? '••••••••' : '' }}"><span class="hint">{{ __('Didapat dari dasbor Fonnte atau Wablas. Disimpan terenkripsi.') }}</span></div>
  <div class="field"><label>{{ __('Kirim pesan uji ke (opsional)') }}</label><input type="text" name="test_to" inputmode="tel" placeholder="08…"></div>
  <div><button class="btn primary">{{ __('Simpan') }}</button></div>
</form>
<form method="post" action="{{ route('admin.settings.voice') }}" class="card stack" style="max-width:560px">@csrf
  <h2>{{ __('Suara (ElevenLabs)') }}</h2>
  <p class="muted" style="font-size:13px">{{ __('Peserta bisa berbicara alih-alih mengetik, dan materi bisa dibacakan. Tanpa kunci, fitur ini memakai suara bawaan browser (mutu lebih rendah, tidak semua browser mendukung).') }}</p>
  <div class="field"><label>{{ __('Kunci API ElevenLabs') }}</label><input type="password" name="el_key" autocomplete="off" placeholder="{{ $hasEl ? '••••••••' : '' }}"><span class="hint">{{ __('Disimpan terenkripsi.') }}</span></div>
  <div class="field"><label>{{ __('ID suara untuk membaca (opsional)') }}</label><input type="text" name="el_voice" value="{{ $elVoice }}" placeholder="JBFqnCBsd6RMkjVDRZzb"><span class="hint">{{ __('Salin dari Voice Library ElevenLabs. Pilih suara yang fasih berbahasa Indonesia.') }}</span></div>
  <div class="grid g2" style="gap:8px">
    <div class="field"><label>{{ __('Model ucapan ke teks') }}</label><input type="text" name="el_stt_model" value="{{ $elStt }}" placeholder="scribe_v1"></div>
    <div class="field"><label>{{ __('Model teks ke suara') }}</label><input type="text" name="el_tts_model" value="{{ $elTts }}" placeholder="eleven_multilingual_v2"></div></div>
  <div class="row"><button class="btn primary">{{ __('Simpan') }}</button>@if ($hasEl)<button class="btn ghost" name="remove" value="1">{{ __('Hapus kunci') }}</button>@endif</div>
</form>
@endsection
