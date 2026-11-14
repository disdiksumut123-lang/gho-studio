@extends('layouts.app')
@section('title', __('Pelatihan saya'))
@section('content')
<div class="head"><div><h1>{{ __('Pelatihan saya') }}</h1><p class="muted">{{ __('Susun kelas dari Pelatihan › Agenda › Mata Pelatihan › Aktivitas.') }}</p></div>
  <button class="btn primary" onclick="document.getElementById('newT').open=true;document.getElementById('tname').focus()">@ic('plus'){{ __('Pelatihan baru') }}</button></div>

<details class="box" id="newT" {{ $trainings->isEmpty() ? 'open' : '' }}>
  <summary>{{ __('Buat pelatihan baru') }}</summary>
  <form method="post" action="{{ route('fas.training.store') }}" class="stack" style="margin-top:12px">@csrf
    <div class="formgrid">
      <div class="field" style="grid-column:1/-1"><label for="tname">{{ __('Nama pelatihan') }}</label><input id="tname" type="text" name="name" required placeholder="{{ __('contoh: Latsar CPNS Golongan III Angkatan XII') }}"></div>
      <div class="field"><label>{{ __('Jenis') }}</label><select name="type">@foreach (\App\Models\Training::TYPES as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach</select></div>
      <div class="field"><label>{{ __('Dibuka') }}</label><input type="datetime-local" name="opens_at"></div>
      <div class="field"><label>{{ __('Ditutup') }}</label><input type="datetime-local" name="closes_at"></div>
    </div>
    <label class="sw"><input type="checkbox" name="use_cohorts" value="1"> {{ __('Gunakan angkatan (boleh dimatikan)') }}</label>
    <div><button class="btn primary">{{ __('Simpan pelatihan') }}</button></div>
  </form>
</details>

@if ($trainings->isEmpty())
  <div class="empty">{{ __('Belum ada pelatihan. Buat yang pertama di atas.') }}</div>
@else
  <div class="grid g3">
    @foreach ($trainings as $t)
      <a class="card stack" href="{{ route('fas.training.show', $t) }}" style="text-decoration:none;color:inherit">
        <div class="row" style="justify-content:space-between"><span class="chip brand">{{ __(\App\Models\Training::TYPES[$t->type] ?? $t->type) }}</span><span class="mono faint">{{ $t->join_code }}</span></div>
        <h2>{{ $t->name }}</h2>
        <div class="row muted" style="font-size:12.5px">@ic('users', 'width:14px;height:14px') {{ __(':n peserta', ['n' => $t->peserta_count]) }}
          @if ($t->opens_at) · @ic('cal', 'width:14px;height:14px') {{ $t->opens_at->translatedFormat('d M Y') }}@endif</div>
      </a>
    @endforeach
  </div>
@endif
@endsection
