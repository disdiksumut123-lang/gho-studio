@extends('layouts.app')
@section('title', __('Pelatihan saya'))
@section('content')
<div class="head"><div><h1>{{ __('Halo, :n', ['n' => \Illuminate\Support\Str::before(auth()->user()->name, ',')]) }}</h1><p class="muted">{{ __('Lanjutkan belajar dari tempat terakhir.') }}</p></div>
  <form method="post" action="{{ route('pes.join') }}" class="row">@csrf<input type="text" name="code" class="mono" placeholder="{{ __('Kode kelas') }}" style="width:150px;text-transform:uppercase" required><button class="btn">{{ __('Gabung') }}</button></form></div>
@foreach ($waiting as $w)
  <div class="card row"><span class="chip {{ $w->status === 'ditolak' ? 'bad' : 'warn' }}">{{ $w->status === 'ditolak' ? __('Ditolak') : __('Menunggu persetujuan') }}</span><b>{{ $w->training->name }}</b></div>
@endforeach
@if ($list->isEmpty())
  <div class="empty">{{ __('Belum ada pelatihan. Masukkan kode kelas dari fasilitator, atau buka tautan yang dibagikan.') }}</div>
@else
  <div class="grid g3">
    @foreach ($list as $x)
      <a class="card stack" href="{{ route('pes.training', $x['t']) }}" style="text-decoration:none;color:inherit">
        <span class="chip brand" style="align-self:flex-start">{{ __(\App\Models\Training::TYPES[$x['t']->type] ?? $x['t']->type) }}</span>
        <h2>{{ $x['t']->name }}</h2>
        <div class="row" style="flex-wrap:nowrap"><div class="bar" style="flex:1"><span style="width:{{ $x['pct'] }}%"></span></div><span class="mono">{{ $x['pct'] }}%</span></div>
      </a>
    @endforeach
  </div>
@endif
@endsection
