@extends('layouts.app')
@section('title', 'CMS')
@section('content')
@php $P = \App\Models\SocialPost::PLATFORMS; $stLabel = ['draf' => [__('Draf'), ''], 'terjadwal' => [__('Terjadwal'), 'brand'], 'diproses' => [__('Diproses'), 'warn'], 'selesai' => [__('Terbit'), 'good'], 'sebagian' => [__('Sebagian terbit'), 'warn'], 'perlu_tindakan' => [__('Perlu tindakan'), 'bad']]; @endphp
<div class="head"><div><h1>{{ __('Content Management System') }}</h1><p class="muted">{{ __('Unggah sekali di sini, terbit di YouTube, Facebook, Instagram, TikTok, dan lainnya.') }}</p></div>
  <a class="btn primary" href="{{ route('cms.create') }}">@ic('send'){{ __('Buat konten') }}</a></div>

<div class="grid g4">
  @foreach (array_slice($P, 0, 4, true) as $k => $m)
    <div class="card stat"><div class="row" style="justify-content:space-between"><span class="label">{{ $m[0] }}</span>@include('partials.plat', ['p' => $k])</div>
      <div class="n" style="font-size:16px">{{ isset($accounts[$k]) ? $accounts[$k]->name : __('Belum terhubung') }}</div>
      <div class="d">{{ isset($accounts[$k]) ? __('Akun tersimpan') : '' }} <a href="{{ route('cms.accounts') }}">{{ isset($accounts[$k]) ? __('Atur') : __('Hubungkan') }}</a></div></div>
  @endforeach
</div>

<div class="split">
  <div class="card pad0">
    <div class="row" style="padding:14px 16px;justify-content:space-between"><h2>{{ __('Semua konten') }}</h2></div>
    <div class="list">
      @forelse ($posts as $p)
        <a class="li" href="{{ route('cms.edit', $p) }}" style="text-decoration:none;color:inherit">
          <span class="chip {{ $stLabel[$p->status][1] ?? '' }}">{{ $stLabel[$p->status][0] ?? $p->status }}</span>
          <div class="grow"><div class="t">{{ $p->title }}</div><div class="s">{{ $p->scheduled_at ? $p->scheduled_at->translatedFormat('D, d M H:i') : $p->updated_at->diffForHumans() }} · {{ $p->media_name ?? __('tanpa media') }}</div></div>
          <span class="row" style="gap:3px;flex-wrap:nowrap">@foreach ($p->platforms ?? [] as $pl)@include('partials.plat', ['p' => $pl, 'size' => 22])@endforeach</span>
        </a>
      @empty
        <div class="li"><div class="empty" style="width:100%">{{ __('Belum ada konten. Tekan "Buat konten".') }}</div></div>
      @endforelse
    </div>
    @if ($posts->hasPages())<div class="pager">@if ($posts->previousPageUrl())<a class="btn sm" href="{{ $posts->previousPageUrl() }}">‹ {{ __('Sebelumnya') }}</a>@else<span></span>@endif @if ($posts->nextPageUrl())<a class="btn sm" href="{{ $posts->nextPageUrl() }}">{{ __('Berikutnya') }} ›</a>@endif</div>@endif
  </div>
  <div class="stack">
    <div class="card"><h3>{{ __('Terjadwal berikutnya') }}</h3>
      <div class="stack" style="margin-top:10px">
        @forelse ($upcoming as $u)
          <div class="row" style="flex-wrap:nowrap"><span class="chip brand mono">{{ $u->scheduled_at->translatedFormat('D H:i') }}</span><b style="flex:1;min-width:0">{{ $u->title }}</b></div>
        @empty <span class="faint">{{ __('Belum ada jadwal.') }}</span> @endforelse
      </div></div>
    <div class="ai-box"><div class="ai-head">@ic('flag'){{ __('Catatan penting') }}</div><p style="font-size:13px">{{ __('Buat unggahan dari CMS Gho Studio. Unggahan yang dibuat langsung di aplikasi Facebook atau TikTok tidak bisa diteruskan otomatis ke platform lain.') }}</p></div>
  </div>
</div>
@endsection
