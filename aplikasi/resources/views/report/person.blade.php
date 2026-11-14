@extends('layouts.app')
@section('title', __('Laporan'))
@section('content')
@php $stc = ['selesai' => 'good', 'terbuka' => 'accent', 'terkunci' => '']; $stl = ['selesai' => __('Selesai'), 'terbuka' => __('Sedang dikerjakan'), 'terkunci' => __('Terkunci')]; @endphp
<div class="crumb">@if ($staff)<a href="{{ route('fas.training.participants', $t) }}">{{ $t->name }}</a>@else<a href="{{ route('pes.training', $t) }}">{{ $t->name }}</a>@endif›<b>{{ __('Laporan') }}</b></div>
<div class="head"><div><h1>{{ __('Laporan belajar') }}</h1><p class="muted">{{ $u->name }}{{ $u->nip ? ' · NIP '.$u->nip : '' }} · {{ $t->name }}</p></div>
  <button class="btn" onclick="print()">@ic('down'){{ __('Cetak / simpan PDF') }}</button></div>
<div class="grid g3">
  <div class="card stat"><span class="label">{{ __('Kemajuan') }}</span><div class="n mono">{{ $rep['pct'] }}%</div><div class="bar"><span style="width:{{ $rep['pct'] }}%"></span></div></div>
  <div class="card stat"><span class="label">{{ __('Aktivitas wajib selesai') }}</span><div class="n mono">{{ $rep['done'] }}/{{ $rep['total'] }}</div></div>
  <div class="card stat"><span class="label">{{ __('Rata-rata nilai') }}</span><div class="n mono">{{ $rep['avg'] ?? '–' }}</div></div>
</div>
@foreach ($rep['agendas'] as $A)
  <section class="card stack">
    <div class="row" style="justify-content:space-between"><div><div class="label">{{ __('Agenda') }}</div><h2>{{ $A['m']->name }}</h2></div>
      <span class="row"><span class="chip {{ $A['pct'] === 100 ? 'good' : '' }} mono">{{ $A['pct'] }}%</span>@if ($A['avg'] !== null)<span class="chip ai mono">{{ __('Nilai') }} {{ $A['avg'] }}</span>@endif</span></div>
    @foreach ($A['subjects'] as $S)
      <div class="stack" style="gap:6px">
        <div class="row" style="justify-content:space-between"><b>{{ $S['m']->name }}</b><span class="row"><span class="mono faint">{{ $S['done'] }}/{{ $S['total'] }}</span>@if ($S['avg'] !== null)<span class="chip ai mono">{{ $S['avg'] }}</span>@endif</span></div>
        <div class="card pad0"><div class="table-wrap"><table>
          <thead><tr><th>{{ __('Aktivitas') }}</th><th>{{ __('Status') }}</th><th>{{ __('Nilai') }}</th><th>{{ __('Selesai') }}</th></tr></thead>
          <tbody>@foreach ($S['acts'] as $x)
            <tr @if ($x['current']) style="background:var(--accent-soft)" @endif><td class="wrap"><b>{{ $x['m']->title }}</b><div class="faint" style="font-size:12px">{{ $x['m']->typeLabel() }}{{ $x['note'] ? ' · '.$x['note'] : '' }}</div></td>
              <td><span class="chip {{ $stc[$x['state']] }}">{{ $x['current'] ? __('Posisi sekarang') : $stl[$x['state']] }}</span></td>
              <td class="mono">{{ $x['score'] ?? '–' }}</td><td class="mono faint">{{ $x['at'] ? \Illuminate\Support\Carbon::parse($x['at'])->translatedFormat('d M H:i') : '' }}</td></tr>
          @endforeach</tbody></table></div></div>
      </div>
    @endforeach
  </section>
@endforeach
@endsection
