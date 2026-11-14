@extends('layouts.app')
@section('title', $a->title)
@php
  use App\Support\Page;
  $page = array_merge(Page::defaults(), (array) $a->cfg('page', []));
  $m = array_merge(Page::defaults()['margin'], (array) ($page['margin'] ?? []));
  [$w, $h] = Page::dims($page);
  $fonts = array_merge(Page::fontDefaults(), (array) $a->cfg('fonts', []));
  $outs = $a->cfg('output.sections', []);
  $secs = $doc->sections ?? [];
  $stLabel = ['draf' => __('Draf'), 'ditinjau' => __('Ditinjau'), 'disetujui' => __('Disetujui'), 'dikembalikan' => __('Dikembalikan')];
  $editable = $staff || $doc->status !== 'disetujui';
@endphp
@push('head')
<style>
  .paper{background:#fff;color:#111;width:{{ $w }}mm;max-width:100%;min-height:{{ $h }}mm;margin:0 auto;padding:{{ $m['top'] }}mm {{ $m['right'] }}mm {{ $m['bottom'] }}mm {{ $m['left'] }}mm;box-shadow:0 2px 18px rgba(0,0,0,.12);border-radius:4px;
    font-family:{!! Page::font($fonts['output_family']) !!};font-size:{{ (float) $fonts['output_size'] }}pt;line-height:{{ (float) $fonts['line_height'] }}}
  .paper h1{font-family:inherit;font-size:{{ (float) $fonts['heading_size'] + 2 }}pt;text-align:center;margin:0 0 4mm}
  .paper .who{text-align:center;margin-bottom:8mm;font-size:.95em}
  .paper h2{font-family:inherit;font-size:{{ (float) $fonts['heading_size'] }}pt;margin:6mm 0 2mm}
  .paper .dhead{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
  .paper .dhead .btn{font-family:"Plus Jakarta Sans",system-ui,sans-serif}
  .paper .dbody{outline:none;min-height:1.5em}
  .paper .dbody[contenteditable=true]:focus{background:#fffbea}
  .paper table{border-collapse:collapse;width:100%}.paper td,.paper th{border:1px solid #444;padding:2mm;vertical-align:top}
  .paper .empty-sec{color:#999;font-style:italic}
  @media (max-width:820px){.paper{padding:18px 16px;min-height:0;width:auto}}
  @page{size:{{ $w }}mm {{ $h }}mm;margin:{{ $m['top'] }}mm {{ $m['right'] }}mm {{ $m['bottom'] }}mm {{ $m['left'] }}mm}
  @media print{.side,.top,.bnav,.noprint,#toast{display:none!important}.main,.content{padding:0!important;margin:0!important}body{background:#fff}
    .paper{box-shadow:none;padding:0;width:auto;min-height:0;border-radius:0}.paper .empty-sec{display:none}.paper h2{break-after:avoid}}
</style>
@endpush
@section('content')
<div class="noprint">
  <div class="crumb"><a href="{{ $staff ? route('fas.training.show', $a->training()) : route('pes.activity', $a) }}">{{ $a->title }}</a>›<span>{{ __('Dokumen') }}</span></div>
  <div class="head"><div><h1>{{ __('Dokumen: :n', ['n' => $u->name]) }}</h1>
    <p class="muted">{{ __('Kertas :s :o · huruf :f :z pt', ['s' => $page['size'] === 'custom' ? $w.'×'.$h.' mm' : $page['size'], 'o' => $page['orientation'] === 'landscape' ? __('mendatar') : __('tegak'), 'f' => $fonts['output_family'], 'z' => $fonts['output_size']]) }}</p></div>
    <div class="row">
      <span class="chip {{ ['disetujui' => 'good', 'dikembalikan' => 'bad'][$doc->status] ?? 'accent' }}">{{ $stLabel[$doc->status] ?? $doc->status }}</span>
      @if ($editable && $outs)<button class="btn ai" type="button" id="composeAll">@ic('spark'){{ __('Susun semua bagian') }}</button>@endif
      <button class="btn" type="button" onclick="print()">@ic('down'){{ __('Cetak / simpan PDF') }}</button>
    </div></div>
  @if ($doc->reviewer_note)<div class="card"><b>{{ __('Catatan peninjau:') }}</b> {{ $doc->reviewer_note }}</div>@endif
  @if (! $doc->answer_id)<div class="card warn">{{ __('Isian belum dikirim. Dokumen disusun dari isian yang tersimpan terakhir.') }}</div>@endif
  @if (! $outs)<div class="empty">{{ __('Fasilitator belum menambahkan bagian output.') }}</div>@endif
</div>

<div class="paper" id="docpage" data-composeurl="{{ route('pes.doc.compose', [$a, $u]) }}" data-update="{{ route('pes.doc.update', [$a, $u]) }}">
  <h1>{{ $a->title }}</h1>
  <div class="who">{{ $u->name }}@if ($u->nip) · NIP {{ $u->nip }}@endif<br>{{ $a->training()->name }}</div>
  @foreach ($outs as $o)
    <section class="dsec" data-key="{{ $o['key'] }}">
      <div class="dhead"><h2>{{ $o['title'] ?? '' }}</h2>@if ($editable)<button class="btn ai sm noprint" type="button" data-compose="{{ $o['key'] }}">@ic('spark'){{ isset($secs[$o['key']]) ? __('Susun ulang') : __('Susun') }}</button>@endif</div>
      <div class="dbody" @if ($editable) contenteditable="true" @endif>@if (isset($secs[$o['key']])){!! $secs[$o['key']] !!}@else<p class="empty-sec">{{ __('Belum disusun. Tekan "Susun".') }}</p>@endif</div>
    </section>
  @endforeach
</div>

@if ($staff)
  <form method="post" action="{{ route('pes.doc.update', [$a, $u]) }}" class="card stack noprint" style="margin-top:18px">@csrf
    <h2>{{ __('Tinjauan') }}</h2>
    <textarea name="note" rows="3" placeholder="{{ __('Catatan untuk peserta (opsional)') }}">{{ $doc->reviewer_note }}</textarea>
    <div class="row"><button class="btn primary" name="status" value="disetujui">@ic('check'){{ __('Setujui') }}</button><button class="btn" name="status" value="dikembalikan">{{ __('Kembalikan untuk diperbaiki') }}</button></div>
  </form>
@endif
<script src="{{ asset('js/gho-pform.js') }}?v=1" defer></script>
@endsection
