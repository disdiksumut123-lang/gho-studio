@extends('layouts.guest')
@section('title', __('Daftar'))
@section('content')
@php $pf = session('invite_prefill', []); @endphp
@if (session('ok'))<div class="chip good" style="white-space:normal;padding:8px 12px">{{ session('ok') }}</div>@endif
<div><h1>{{ __('Daftar peserta') }}</h1><p class="muted">{{ __('Masukkan kode kelas dari fasilitator agar langsung masuk ke pelatihan.') }}</p></div>
<form method="post" action="{{ url('/daftar') }}" class="stack">@csrf
  @unless (session('invite_token'))<div class="field"><label for="code">{{ __('Kode kelas') }}</label><input id="code" type="text" name="code" value="{{ old('code', request('kode', session('share_code'))) }}" class="mono" placeholder="LAT-1234" style="text-transform:uppercase"></div>@endunless
  <div class="field"><label for="name">{{ __('Nama lengkap dan gelar') }}</label><input id="name" type="text" name="name" value="{{ old('name', $pf['name'] ?? '') }}" required></div>
  <div class="field"><label for="email">{{ __('Email') }}</label><input id="email" type="email" name="email" value="{{ old('email', $pf['email'] ?? '') }}" required autocomplete="username"></div>
  <div class="field"><label for="wa">{{ __('Nomor WhatsApp') }}</label><input id="wa" type="text" inputmode="tel" name="whatsapp" value="{{ old('whatsapp', $pf['whatsapp'] ?? '') }}" placeholder="08…"></div>
  <div class="field"><label for="nip">{{ __('NIP') }}</label><input id="nip" type="text" inputmode="numeric" name="nip" value="{{ old('nip', $pf['nip'] ?? '') }}"></div>
  <div class="field"><label for="password">{{ __('Kata sandi') }}</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"><span class="hint">{{ __('Minimal 8 karakter.') }}</span></div>
  <div class="field"><label for="password_confirmation">{{ __('Ulangi kata sandi') }}</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
  <button class="btn primary" style="justify-content:center">{{ __('Daftar') }}</button>
</form>
<p class="muted" style="font-size:13px">{{ __('Sudah punya akun?') }} <a href="{{ route('login') }}">{{ __('Masuk') }}</a></p>
@endsection
