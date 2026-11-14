@extends('layouts.app')
@section('title', $a->title)
@section('content')
@php $s = $a->subject; @endphp
<div class="crumb"><a href="{{ route('pes.training', $t) }}">{{ $t->name }}</a>›<span>{{ $s->agenda->name }}</span>›<span>{{ $s->name }}</span></div>
<div class="head"><div><div class="label">{{ $a->typeLabel() }}@if ($a->owner_role === 'coach') · {{ __('Bimbingan coach') }}@endif</div><h1 style="margin-top:4px">{{ $a->title }}</h1>
  @if ($a->closes_at)<p class="muted">{{ __('Batas waktu: :w', ['w' => $a->closes_at->translatedFormat('l, d F Y H:i')]) }}</p>@endif</div>
  <div class="row">
    <span class="chip {{ $done ? 'good' : 'accent' }}" id="doneChip">{{ $done ? '✓ '.__('Selesai') : __('Belum selesai') }}</span>
    <a class="btn ghost" href="{{ route('pes.report', $t) }}">@ic('grid'){{ __('Laporan') }}</a>
    @if ($next)<a class="btn {{ $done ? 'primary' : '' }}" id="nextBtn" href="{{ route('pes.activity', $next) }}" @unless ($done) hidden @endunless>{{ __('Berikutnya') }} ›</a>@endif
  </div></div>

@if (trim(strip_tags((string) $a->instructions, '<img>')) !== '' && $a->type !== 'studi_kasus')
  <div class="card stack"><div class="prose" data-speak>{!! $a->instructions !!}</div></div>
@endif

@switch($a->type)
  @case('materi')
    @unless ($done)<form method="post" action="{{ route('pes.complete', $a) }}">@csrf<button class="btn primary">@ic('check'){{ __('Saya sudah membaca, tandai selesai') }}</button></form>@endunless
    @break

  @case('video')
  @case('audio')
    @php $url = (string) $a->cfg('url'); preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $url, $yt); @endphp
    <div class="card stack" id="media" data-url="{{ route('pes.media', $a) }}" data-max="{{ $media->max_position ?? 0 }}">
      @if ($a->type === 'video' && ! empty($yt[1]))
        <div class="frame" style="overflow:hidden"><div id="ytp" data-id="{{ $yt[1] }}"></div></div>
      @elseif ($a->type === 'video')
        <video id="mel" class="frame" controls controlslist="nodownload noplaybackrate" preload="metadata" src="{{ $url }}"></video>
      @else
        <audio id="mel" controls controlslist="nodownload noplaybackrate" preload="metadata" src="{{ $url }}" style="width:100%"></audio>
      @endif
      <div class="row" style="flex-wrap:nowrap"><div class="bar" style="flex:1"><span id="mbar" style="width:0%"></span></div><span class="mono" id="mpct">0%</span></div>
      <div class="faint" style="font-size:12.5px">@ic('lock', 'width:13px;height:13px;vertical-align:-2px') {{ $a->type === 'video' ? __('Tonton sampai selesai. Bagian yang belum ditonton tidak bisa dilompati.') : __('Dengarkan sampai selesai. Tidak bisa melompat ke depan.') }}</div>
    </div>
    @break

  @case('ebook')
    @php $need = (int) $a->cfg('pages', 0) * (int) $a->cfg('seconds_per_page', 20); @endphp
    <div class="card stack" id="ebook" data-url="{{ route('pes.media', $a) }}" data-need="{{ $need }}" data-have="{{ $media->active_seconds ?? 0 }}">
      <iframe class="frame ebook" src="{{ $a->cfg('url') }}" allowfullscreen loading="lazy" title="{{ $a->title }}"></iframe>
      <div class="row" style="flex-wrap:nowrap"><div class="bar" style="flex:1"><span id="ebar" style="width:0%"></span></div><span class="mono" id="etime"></span></div>
      <label class="sw"><input type="checkbox" id="elast" @checked($done)> {{ __('Saya sudah membaca sampai halaman terakhir') }}</label>
      <div class="faint" style="font-size:12.5px">@ic('lock', 'width:13px;height:13px;vertical-align:-2px') {{ __('Baca semua :p halaman. Waktu baca minimal dihitung hanya saat halaman ini terbuka.', ['p' => $a->cfg('pages', 0)]) }}</div>
    </div>
    @break

  @case('tugas')
  @case('refleksi')
    @php $fb = $submitted?->feedback; $showFb = $fb && in_array($fb->status, ['auto', 'approved']); @endphp
    @if ($submitted)
      <div class="ai-box stack">
        <div class="ai-head">@ic('chat'){{ __('Tanggapan') }} @if ($fb && $fb->total !== null)<span class="chip ai mono">{{ $fb->total }}</span>@endif</div>
        @if ($fb && $fb->status === 'returned')
          <div><span class="chip bad">{{ __('Dikembalikan untuk diperbaiki') }}</span><p style="margin-top:6px" class="ai-text">{{ $fb->reviewer_note }}</p></div>
        @elseif ($showFb)
          <p class="ai-text" data-speak>{{ $fb->content }}</p>
          @if ($fb->scores)<div class="stack" style="font-size:13px">@foreach ($fb->scores as $sc)<div class="row" style="flex-wrap:nowrap"><b style="flex:1">{{ $sc['criterion'] ?? '' }}</b><span class="mono">{{ $sc['score'] ?? '' }}</span></div>@if (! empty($sc['reason']))<div class="faint" style="margin-top:-6px">{{ $sc['reason'] }}</div>@endif @endforeach</div>@endif
          @if ($fb->reviewer_note)<p style="font-size:13px"><b>{{ __('Catatan :r:', ['r' => $a->owner_role === 'coach' ? __('coach') : __('fasilitator')]) }}</b> {{ $fb->reviewer_note }}</p>@endif
        @elseif ($fb)
          <p class="faint">{{ __('Jawaban sudah diterima. Tanggapan sedang ditinjau.') }}</p>
        @else
          <form method="post" action="{{ route('pes.answer.feedback', $submitted) }}">@csrf<p class="faint" style="margin-bottom:8px">{{ __('Jawaban tersimpan, tetapi tanggapan belum dibuat.') }}</p><button class="btn ai sm">@ic('spark'){{ __('Minta tanggapan') }}</button></form>
        @endif
        <div class="faint" style="font-size:12px">{{ __('Dikirim :w · versi :v', ['w' => $submitted->created_at->translatedFormat('d M H:i'), 'v' => $submitted->version]) }}@if ($submitted->file_name) · <a href="{{ route('pes.answer.file', $submitted) }}">{{ $submitted->file_name }}</a>@endif</div>
      </div>
    @endif
    <form method="post" action="{{ route('pes.answer.submit', $a) }}" enctype="multipart/form-data" class="stack" onsubmit="ghoEditorSync('edAns')">@csrf
      <div class="row" style="justify-content:space-between"><h2>{{ $submitted ? __('Perbaiki jawaban') : __('Jawabanmu') }}</h2><span class="chip {{ $a->allow_upload ? 'good' : 'warn' }}">{{ $a->allow_upload ? __('Boleh ketik atau unggah') : __('Wajib ketik langsung') }}</span></div>
      @include('partials.editor', ['id' => 'edAns', 'name' => 'body', 'html' => $answer->body ?? '', 'upload' => $a->allow_upload, 'save' => route('pes.answer.save', $a), 'limits' => ['paste' => $a->cfg('allow_paste', true), 'chars' => (int) $a->cfg('max_chars', 0), 'voice' => (int) $a->cfg('max_voice', 60)]])
      <div class="row"><button class="btn primary">@ic('send'){{ __('Kirim jawaban') }}</button><span class="faint" style="font-size:12px">{{ __('Setiap perubahan tersimpan otomatis sebagai versi baru.') }}</span></div>
    </form>
    @break

  @case('studi_kasus')
    <div class="split">
      <div class="card stack" id="case" data-start="{{ route('pes.case.start', $a) }}" data-session="{{ $case?->id }}" data-answer="{{ url('/belajar/kasus/__ID__/jawab') }}" data-respond="{{ url('/belajar/kasus/__ID__/tanggapan') }}">
        <div class="row" style="justify-content:space-between"><h2>{{ __('Studi kasus') }}</h2><div class="rounds" id="crounds"></div></div>
        <div class="chat" id="cchat"></div>
        <div id="cform" class="stack" hidden>
          <textarea id="cinput" data-paste="{{ $a->cfg('allow_paste', true) ? 1 : 0 }}" data-maxchars="{{ (int) $a->cfg('max_chars', 0) }}" data-maxvoice="{{ (int) $a->cfg('max_voice', 60) }}" @if ((int) $a->cfg('max_chars', 0)) maxlength="{{ (int) $a->cfg('max_chars') }}" @endif placeholder="{{ __('Tulis keputusan dan alasanmu…') }}"></textarea>
          <div class="row"><button class="btn primary" type="button" id="csend">@ic('send'){{ __('Kirim jawaban') }}</button><span class="faint" style="font-size:12px">{{ __('Jawaban disimpan dulu sebelum situasi berikutnya dibuat.') }}</span></div>
        </div>
        <div class="row" id="cstartRow"><button class="btn primary" type="button" id="cstart">@ic('play'){{ $case ? __('Lanjutkan') : __('Mulai studi kasus') }}</button></div>
      </div>
      <div class="stack">
        @if (trim(strip_tags((string) $a->instructions)) !== '')<div class="card prose"><h3>{{ __('Petunjuk') }}</h3>{!! $a->instructions !!}</div>@endif
        <div class="ai-box"><div class="ai-head">@ic('flag'){{ __('Cara kerja') }}</div><div class="stack" style="font-size:13px">
          <div>{{ __('Setiap jawabanmu ditanggapi, lalu situasi berubah. Sesuaikan keputusanmu.') }}</div>
          <div>{{ __('Jumlah putaran: :n.', ['n' => (int) $a->cfg('rounds', 3)]) }}</div>
          <div>{{ __('Semua jawaban tersimpan. Jika koneksi terputus, buka lagi halaman ini untuk melanjutkan.') }}</div></div></div>
      </div>
    </div>
    @break

  @case('kuis')
    @php $qs = $a->questions; @endphp
    @if ($attempt)<div class="card row" style="justify-content:space-between"><span>{{ __('Nilai terakhir') }}</span><b class="mono" style="font-size:22px;color:{{ $attempt->score >= (int) $a->cfg('passing', 70) ? 'var(--good)' : 'var(--bad)' }}">{{ $attempt->score }}</b><span class="faint">{{ __('Batas lulus :p', ['p' => (int) $a->cfg('passing', 70)]) }}</span></div>@endif
    @if ($qs->isEmpty())<div class="empty">{{ __('Kuis belum memiliki soal.') }}</div>@else
    <form method="post" action="{{ route('pes.quiz', $a) }}" class="stack" id="quizForm">@csrf
      @foreach ($qs as $i => $q)
        @php $d = $q->data ?? []; $res = $attempt?->answers[$q->id] ?? null; @endphp
        <div class="card stack"><div class="row" style="justify-content:space-between"><span class="label">{{ __('Soal') }} {{ $i + 1 }} · {{ __(\App\Models\QuizQuestion::TYPES[$q->type] ?? '') }}</span>@if ($res)<span class="chip {{ $res['ok'] ? 'good' : 'bad' }}">{{ $res['ok'] ? __('Benar') : __('Salah') }}</span>@endif</div>
          <div class="prose"><b>{!! nl2br(e($q->prompt)) !!}</b></div>
          @switch($q->type)
            @case('mcq')
              @foreach ($d['options'] ?? [] as $k => $o)<label class="opt"><input type="radio" name="q[{{ $q->id }}]" value="{{ $k }}" required> <span>{{ $o }}</span></label>@endforeach @break
            @case('mcq_multi')
              <span class="faint" style="font-size:12px">{{ __('Pilih semua jawaban yang benar.') }}</span>
              @foreach ($d['options'] ?? [] as $k => $o)<label class="opt"><input type="checkbox" name="q[{{ $q->id }}][]" value="{{ $k }}"> <span>{{ $o }}</span></label>@endforeach @break
            @case('tf_multi')
              <table class="bst">@foreach ($d['statements'] ?? [] as $k => $st)<tr><td class="wrap">{{ $st }}</td><td style="white-space:nowrap"><label><input type="radio" name="q[{{ $q->id }}][{{ $k }}]" value="1" required> {{ __('Benar') }}</label> <label><input type="radio" name="q[{{ $q->id }}][{{ $k }}]" value="0"> {{ __('Salah') }}</label></td></tr>@endforeach</table> @break
            @case('matching')
              @foreach ($d['left'] ?? [] as $k => $l)<div class="jod"><b>{{ $l }}</b><select name="q[{{ $q->id }}][{{ $k }}]" required><option value="">{{ __('Pilih pasangan…') }}</option>@foreach ($d['right'] ?? [] as $rk => $rv)<option value="{{ $rk }}">{{ $rv }}</option>@endforeach</select></div>@endforeach @break
            @case('ordering')
              <span class="faint" style="font-size:12px">{{ __('Urutkan dengan tombol panah.') }}</span>
              <div class="stack ordering" style="gap:6px">@foreach ($d['items'] ?? [] as $k => $it)<div class="urut"><span class="n">{{ $loop->iteration }}</span><span style="flex:1">{{ $it }}</span><input type="hidden" name="q[{{ $q->id }}][]" value="{{ $k }}"><button type="button" onclick="ghoMove(this,-1)" aria-label="{{ __('Naik') }}">↑</button><button type="button" onclick="ghoMove(this,1)" aria-label="{{ __('Turun') }}">↓</button></div>@endforeach</div> @break
            @case('short')
              <input type="text" name="q[{{ $q->id }}]" required autocomplete="off"> @break
          @endswitch
          @if ($res && ! $res['ok'] && $q->explanation)<div class="faint" style="font-size:13px">{{ $q->explanation }}</div>@endif
        </div>
      @endforeach
      <div><button class="btn primary">@ic('check'){{ $attempt ? __('Kirim ulang') : __('Kirim jawaban') }}</button></div>
    </form>
    @endif
    @break
@endswitch
@endsection
