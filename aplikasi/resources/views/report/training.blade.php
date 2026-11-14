@extends('layouts.app')
@section('title', __('Laporan kelas'))
@section('content')
<div class="crumb"><a href="{{ route('fas.training.show', $t) }}">{{ $t->name }}</a>›<b>{{ __('Laporan kelas') }}</b></div>
<div class="head"><div><h1>{{ __('Laporan kelas') }}</h1><p class="muted">{{ __('Kemajuan setiap peserta per agenda. Klik nama untuk laporan rinci per mata pelatihan dan aktivitas.') }}</p></div>
  <div class="row">
    @if ($t->use_cohorts && $t->cohorts->count())<form method="get"><select name="angkatan" onchange="this.form.submit()" style="width:auto"><option value="">{{ __('Semua angkatan') }}</option>@foreach ($t->cohorts as $c)<option value="{{ $c->id }}" @selected(request('angkatan') == $c->id)>{{ $c->name }}</option>@endforeach</select></form>@endif
    <a class="btn" href="{{ request()->fullUrlWithQuery(['unduh' => 1]) }}">@ic('down'){{ __('Unduh Excel (CSV)') }}</a></div></div>
<div class="card pad0"><div class="table-wrap"><table>
  <thead><tr><th>{{ __('Peserta') }}</th>@foreach ($t->agendas as $a)<th title="{{ $a->name }}">{{ __('Agenda') }} {{ $loop->iteration }}</th>@endforeach<th>{{ __('Total') }}</th><th>{{ __('Nilai') }}</th></tr></thead>
  <tbody>@forelse ($rows as $x)
    <tr><td><a href="{{ route('fas.report.user', [$t, $x['e']->user]) }}"><b>{{ $x['e']->user->name }}</b></a><div class="faint" style="font-size:12px">{{ $x['e']->cohort?->name }}</div></td>
      @foreach ($x['rep']['agendas'] as $A)<td><div class="row" style="flex-wrap:nowrap"><div class="bar" style="width:60px"><span style="width:{{ $A['pct'] }}%"></span></div><span class="mono" style="font-size:12px">{{ $A['pct'] }}%</span></div></td>@endforeach
      <td class="mono"><b>{{ $x['rep']['pct'] }}%</b></td><td class="mono">{{ $x['rep']['avg'] ?? '–' }}</td></tr>
  @empty <tr><td colspan="99" class="faint">{{ __('Belum ada peserta aktif.') }}</td></tr> @endforelse</tbody></table></div></div>
@endsection
