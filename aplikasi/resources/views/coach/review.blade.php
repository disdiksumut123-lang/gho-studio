@extends('layouts.app')
@section('title', __('Tinjau jawaban'))
@section('content')
@php $tabs = ['menunggu' => __('Menunggu'), 'auto' => __('Terkirim otomatis'), 'approved' => __('Disetujui'), 'returned' => __('Dikembalikan'), 'semua' => __('Semua')];
  $base = $role === 'coach' ? 'coach.review' : 'fas.review'; @endphp
<div class="head"><div><h1>{{ __('Tinjau jawaban') }}</h1><p class="muted">{{ $role === 'coach' ? __('Jawaban pada aktivitas bimbingan coach.') : __('Jawaban pada aktivitas fasilitator.') }}</p></div></div>
<div class="tabs">@foreach ($tabs as $k => $v)<button type="button" class="{{ $status === $k ? 'on' : '' }}" onclick="location.href='{{ route($base, ['status' => $k]) }}'">{{ $v }}</button>@endforeach</div>
@forelse ($items as $f)
  @php $ans = $f->answer; $act = $ans->activity; @endphp
  <div class="card stack">
    <div class="row" style="justify-content:space-between"><div><b>{{ $ans->user->name }}</b> <span class="faint">· {{ $act->title }} · {{ $act->subject->agenda->training->name }}</span></div>
      <span class="row"><span class="chip">{{ $tabs[$f->status] ?? $f->status }}</span>@if ($f->total !== null)<span class="chip ai mono">{{ $f->total }}</span>@endif</span></div>
    <details class="box"><summary>{{ __('Jawaban peserta') }} ({{ __('versi :v', ['v' => $ans->version]) }})</summary><div class="prose" style="margin-top:8px">{!! $ans->body !!}</div>
      @if ($ans->file_name)<p style="margin-top:6px">{{ __('Berkas:') }} <a href="{{ route('pes.answer.file', $ans) }}">{{ $ans->file_name }}</a></p>@endif</details>
    <form method="post" action="{{ route('feedback.approve', $f) }}" class="stack">@csrf
      <div class="field"><label>{{ __('Tanggapan untuk peserta (boleh diedit)') }}</label><textarea name="content">{{ $f->content }}</textarea></div>
      <div class="field"><label>{{ __('Catatan tambahan') }}</label><input type="text" name="note" value="{{ $f->reviewer_note }}"></div>
      <div class="row"><button class="btn primary sm">@ic('check'){{ __('Setujui dan kirim') }}</button>
        <button class="btn sm" type="button" onclick="const n=prompt({{ \Illuminate\Support\Js::from(__('Apa yang perlu diperbaiki peserta?')) }});if(n){const fm=document.getElementById('ret{{ $f->id }}');fm.note.value=n;fm.submit()}">{{ __('Kembalikan untuk diperbaiki') }}</button></div>
    </form>
    <form method="post" action="{{ route('feedback.return', $f) }}" id="ret{{ $f->id }}" hidden>@csrf<input type="hidden" name="note"></form>
  </div>
@empty
  <div class="empty">{{ __('Tidak ada jawaban di sini.') }}</div>
@endforelse
@if ($items->hasPages())<div class="row">@if ($items->previousPageUrl())<a class="btn sm" href="{{ $items->previousPageUrl() }}">‹ {{ __('Sebelumnya') }}</a>@endif @if ($items->nextPageUrl())<a class="btn sm" href="{{ $items->nextPageUrl() }}">{{ __('Berikutnya') }} ›</a>@endif</div>@endif
@endsection
