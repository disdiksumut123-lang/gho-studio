@extends('layouts.app')
@section('title', __('Akun terhubung'))
@section('content')
<div class="head"><div><h1>{{ __('Akun terhubung') }}</h1><p class="muted">{{ __('Simpan akun media sosial lembaga. Token disimpan terenkripsi dan bisa dicabut kapan saja.') }}</p></div></div>
<div class="grid g2">
  @foreach (\App\Models\SocialPost::PLATFORMS as $k => $m)
    @php $a = $accounts[$k] ?? null; @endphp
    <form method="post" action="{{ route('cms.accounts.save') }}" class="card stack">@csrf <input type="hidden" name="platform" value="{{ $k }}">
      <div class="row" style="justify-content:space-between">@include('partials.plat', ['p' => $k, 'size' => 30])<b style="flex:1">{{ $m[0] }}</b><span class="chip {{ $a ? 'good' : '' }}">{{ $a ? __('Tersimpan') : __('Belum terhubung') }}</span></div>
      <div class="field"><label>{{ __('Nama akun atau halaman') }}</label><input type="text" name="name" value="{{ $a?->name }}" required></div>
      <div class="field"><label>{{ __('ID halaman atau kanal (opsional)') }}</label><input type="text" name="external_id" value="{{ $a?->external_id }}"></div>
      <div class="field"><label>{{ __('Token akses') }}</label><input type="password" name="token" placeholder="{{ $a?->token ? '••••••••' : '' }}" autocomplete="off"><span class="hint">{{ __('Diberikan oleh programmer setelah aplikasi resmi platform disetujui.') }}</span></div>
      <div class="row"><button class="btn sm primary">{{ __('Simpan') }}</button></div>
    </form>
  @endforeach
</div>
@endsection
