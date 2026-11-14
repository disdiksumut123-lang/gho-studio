@extends('layouts.guest')
@section('title', __('Masuk'))
@section('content')
@if (session('ok'))<div class="chip good" style="white-space:normal;padding:8px 12px">{{ session('ok') }}</div>@endif
<div><h1>{{ __('Masuk') }}</h1><p class="muted">{{ __('Satu akun untuk semua peran Anda.') }}</p></div>
<form method="post" action="{{ url('/login') }}" class="stack">@csrf
  <div class="field"><label for="email">{{ __('Email') }}</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
  <div class="field"><label for="password">{{ __('Kata sandi') }}</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
  <label class="sw"><input type="checkbox" name="remember" value="1" checked> {{ __('Ingat saya') }}</label>
  <button class="btn primary" style="justify-content:center">{{ __('Masuk') }}</button>
</form>
<p class="muted" style="font-size:13px">{{ __('Peserta baru?') }} <a href="{{ route('register') }}">{{ __('Daftar dengan kode kelas') }}</a></p>
@endsection
