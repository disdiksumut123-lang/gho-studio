@extends('layouts.app')
@section('title', __('Beranda coach'))
@section('content')
<div class="head"><div><h1>{{ __('Beranda coach') }}</h1><p class="muted">{{ __('Bimbingan rancangan aktualisasi, aksi perubahan, proyek perubahan, learning journal, dan lesson learned.') }}</p></div>
  <a class="btn primary" href="{{ route('coach.review') }}">@ic('inbox'){{ __('Tinjau jawaban') }} @if ($pending)<span class="chip accent">{{ $pending }}</span>@endif</a></div>
<div class="grid g3">
  <div class="card stat"><span class="label">{{ __('Pelatihan dibimbing') }}</span><div class="n mono">{{ $trainings->count() }}</div></div>
  <div class="card stat"><span class="label">{{ __('Aktivitas coach') }}</span><div class="n mono">{{ $acts->count() }}</div></div>
  <div class="card stat"><span class="label">{{ __('Menunggu ditinjau') }}</span><div class="n mono">{{ $pending }}</div></div>
</div>
<div class="split">
  <div class="card pad0"><div style="padding:14px 16px"><h2>{{ __('Aktivitas bimbingan') }}</h2></div><div class="list">
    @forelse ($acts as $a)
      <div class="li">@ic($a->icon(), 'width:18px;height:18px')<div class="grow"><div class="t">{{ $a->title }}</div><div class="s">{{ $a->subject->agenda->training->name }} · {{ $a->typeLabel() }}</div></div>
        @if (auth()->user()->hasRole('fasilitator'))<a class="btn sm" href="{{ route('fas.activity.edit', $a) }}">{{ __('Ubah') }}</a>@endif</div>
    @empty
      <div class="li"><span class="faint">{{ __('Belum ada aktivitas coach. Fasilitator memilih "Dikelola oleh: Coach" saat membuat aktivitas.') }}</span></div>
    @endforelse
  </div></div>
  <div class="stack">
    <div class="card"><h3>{{ __('Bagian coach per pelatihan') }}</h3><div class="stack" style="margin-top:8px;font-size:13px">
      @foreach (\App\Http\Controllers\Coach\CoachController::COACH_KINDS as $k => $list)<div><b>{{ __(\App\Models\Training::TYPES[$k]) }}</b>: {{ implode(', ', array_map('__', $list)) }}</div>@endforeach</div></div>
    <div class="ai-box"><div class="ai-head">@ic('spark'){{ __('Segera hadir di tahap berikutnya') }}</div><div class="stack" style="font-size:13px">
      <div>{{ __('Sesi GROW, catatan tiga pihak coach–mentor–peserta, papan milestone dengan peringatan dini, kartu kemajuan untuk mentor, gladi seminar dengan penguji simulasi, peta pemangku kepentingan, mesin waktu dampak, dan galeri praktik baik.') }}</div></div></div>
  </div>
</div>
@endsection
