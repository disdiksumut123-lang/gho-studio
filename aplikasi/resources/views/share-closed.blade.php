@extends('layouts.guest')
@section('title', __('Tautan belum dibuka'))
@section('content')
<div class="stack"><h1>{{ $st === 'belum' ? __('Belum dibuka') : __('Sudah ditutup') }}</h1>
<p class="muted">{{ $l->levelLabel() }}: <b>{{ $l->label }}</b></p>
@if ($st === 'belum')<p>{{ __('Tautan ini bisa dibuka mulai :w.', ['w' => $l->opens_at->translatedFormat('l, d F Y H:i')]) }}</p>
@else<p>{{ __('Waktu akses berakhir pada :w. Hubungi fasilitator bila perlu dibuka lagi.', ['w' => $l->closes_at->translatedFormat('l, d F Y H:i')]) }}</p>@endif
<a class="btn" href="{{ url('/') }}">{{ __('Ke beranda') }}</a></div>
@endsection
