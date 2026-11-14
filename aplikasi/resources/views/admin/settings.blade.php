@extends('layouts.app')
@section('title', 'WhatsApp')
@section('content')
<div class="head"><div><h1>{{ __('Undangan lewat WhatsApp') }}</h1><p class="muted">{{ __('Tanpa gateway, tombol WA membuka WhatsApp dengan pesan undangan siap kirim. Dengan gateway, undangan terkirim otomatis sekaligus.') }}</p></div></div>
<form method="post" action="{{ route('admin.settings.save') }}" class="card stack" style="max-width:560px">@csrf
  <div class="field"><label>{{ __('Gateway') }}</label><select name="wa_gateway">@foreach (\App\Services\WhatsApp::GATEWAYS as $k => $v)<option value="{{ $k }}" @selected($gw === $k)>{{ __($v) }}</option>@endforeach</select></div>
  <div class="field"><label>{{ __('Alamat server (khusus Wablas)') }}</label><input type="url" name="wa_base_url" value="{{ $base }}" placeholder="https://jkt.wablas.com"></div>
  <div class="field"><label>{{ __('Token') }}</label><input type="password" name="wa_token" autocomplete="off" placeholder="{{ $hasToken ? '••••••••' : '' }}"><span class="hint">{{ __('Didapat dari dasbor Fonnte atau Wablas. Disimpan terenkripsi.') }}</span></div>
  <div class="field"><label>{{ __('Kirim pesan uji ke (opsional)') }}</label><input type="text" name="test_to" inputmode="tel" placeholder="08…"></div>
  <div><button class="btn primary">{{ __('Simpan') }}</button></div>
</form>
@endsection
