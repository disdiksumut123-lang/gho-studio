@extends('layouts.app')
@section('title', $t->name)
@section('content')
<div class="crumb"><a href="{{ route('pes.home') }}">{{ __('Pelatihan saya') }}</a>›<b>{{ $t->name }}</b></div>
<div class="head"><div><h1>{{ $t->name }}</h1><p class="muted">{{ __('Kerjakan berurutan. Item terkunci akan terbuka setelah yang sebelumnya selesai.') }}</p></div>
  <div class="row"><div class="row" style="flex-wrap:nowrap;min-width:180px"><div class="bar" style="flex:1"><span style="width:{{ $pct }}%"></span></div><b class="mono">{{ $pct }}%</b></div>
    <a class="btn" href="{{ route('pes.report', $t) }}">@ic('grid'){{ __('Laporan saya') }}</a></div></div>
@forelse ($t->agendas as $ag)
  <section class="card stack" id="ag{{ $ag->id }}">
    <div class="row" style="justify-content:space-between"><div><div class="label">{{ __('Agenda') }} {{ $loop->iteration }}</div><h2>{{ $ag->name }}</h2></div>
      @if ($w = \App\Services\Progress::inWindow($ag))<span class="chip warn">{{ \App\Services\Progress::reasonText($w) }}</span>@endif</div>
    @foreach ($ag->subjects as $s)
      <div class="stack" id="mp{{ $s->id }}" style="gap:8px">
        <div class="label" style="margin-top:4px">{{ $s->name }}</div>
        <div class="path">
          @foreach ($s->activities as $a)
            @php $st = $status[$a->id]; $done = $st['reason'] === 'selesai'; $lock = ! $st['ok']; @endphp
            @if ($lock)
              <div class="pstep lock" title="{{ \App\Services\Progress::reasonText($st['reason']) }}"><span class="ix">@ic('lock')</span><span class="grow"><b style="font-size:13.5px">{{ $a->title }}</b><div class="faint" style="font-size:12px">{{ $a->typeLabel() }} · {{ \App\Services\Progress::reasonText($st['reason']) }}</div></span><span class="chip">{{ __('Terkunci') }}</span></div>
            @else
              <a class="pstep {{ $done ? 'done' : '' }}" href="{{ route('pes.activity', $a) }}" style="text-decoration:none"><span class="ix">@ic($done ? 'check' : $a->icon())</span><span class="grow"><b style="font-size:13.5px">{{ $a->title }}</b><div class="faint" style="font-size:12px">{{ $a->typeLabel() }}@if ($a->closes_at) · {{ __('tutup :w', ['w' => $a->closes_at->translatedFormat('d M H:i')]) }}@endif</div></span>
                <span class="chip {{ $done ? 'good' : 'accent' }}">{{ $done ? __('Selesai') : __('Kerjakan') }}</span></a>
            @endif
          @endforeach
        </div>
      </div>
    @endforeach
  </section>
@empty
  <div class="empty">{{ __('Fasilitator belum menyusun isi pelatihan.') }}</div>
@endforelse
@endsection
