@extends('layouts.app')
@section('title', $t->name)
@section('content')
<div class="crumb"><a href="{{ route('fas.home') }}">{{ __('Pelatihan saya') }}</a>›<b>{{ $t->name }}</b></div>
<div class="head"><div><div class="label">{{ __(\App\Models\Training::TYPES[$t->type] ?? $t->type) }}</div><h1 style="margin-top:4px">{{ $t->name }}</h1>
  <p class="muted">{{ __('Kode gabung peserta:') }} <b class="mono">{{ $t->join_code }}</b> · {{ __('Tautan daftar:') }} <span class="mono" style="font-size:12px">{{ route('register', ['kode' => $t->join_code]) }}</span></p></div>
  <div class="row"><a class="btn" href="{{ route('fas.training.participants', $t) }}">@ic('users'){{ __('Peserta & angkatan') }}</a>
    <a class="btn" href="{{ route('fas.report', $t) }}">@ic('grid'){{ __('Laporan') }}</a>
    <a class="btn" href="{{ route('pes.training', $t) }}">@ic('play'){{ __('Lihat sebagai peserta') }}</a></div></div>

@if (session('share'))<div class="card row" style="border-color:var(--brand)"><b>{{ __('Tautan siap dibagikan:') }}</b><input type="text" readonly value="{{ session('share') }}" style="flex:1;min-width:200px" onclick="this.select()"><button type="button" class="btn sm primary" onclick="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from(session('share')) }}).then(()=>ghoToast({{ \Illuminate\Support\Js::from(__('Tautan disalin')) }}))">{{ __('Salin') }}</button>
  <a class="btn sm" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode(session('share')) }}">WhatsApp</a></div>@endif
<div class="split">
  <div class="card stack">
    <div class="row" style="justify-content:space-between"><h2>{{ __('Struktur kelas') }}</h2>
      <div class="row" style="gap:6px">@include('partials.item-tools', ['level' => 'pelatihan', 'item' => $t, 'label' => $t->name])</div></div>
    <p class="faint" style="font-size:12.5px">{{ __('"Berurutan" aktif berarti item sebelumnya wajib selesai dulu. Matikan untuk membebaskan urutan.') }}</p>
    <div class="tree">
      @forelse ($t->agendas as $ag)
        <details open>
          <summary><span class="lv">{{ __('Agenda') }} {{ $loop->iteration }}</span><span class="nm">{{ $ag->name }}</span>@include('partials.item-tools', ['level' => 'agenda', 'item' => $ag, 'label' => $ag->name])</summary>
          @foreach ($ag->subjects as $s)
            <details open>
              <summary><span class="lv">{{ __('Mata pelatihan') }}</span><span class="nm">{{ $s->name }}</span>@include('partials.item-tools', ['level' => 'mapel', 'item' => $s, 'label' => $s->name])</summary>
              @foreach ($s->activities as $a)
                <div class="act">
                  <span class="ix" style="width:28px;height:28px;border-radius:8px;display:grid;place-items:center;background:var(--surface-2)">@ic($a->icon(), 'width:14px;height:14px')</span>
                  <a class="nm" href="{{ route('fas.activity.edit', $a) }}"><b>{{ $a->title }}</b></a>
                  <span class="chip">{{ $a->typeLabel() }}</span>
                  @if ($a->owner_role === 'coach')<span class="chip accent">{{ __('Coach') }}</span>@endif
                  @if (! $a->required)<span class="chip">{{ __('Tidak wajib') }}</span>@endif
                  @include('partials.item-tools', ['level' => 'aktivitas', 'item' => $a, 'label' => $a->title])
                </div>
              @endforeach
              <div class="act"><a class="btn sm" href="{{ route('fas.activity.create', $s) }}">@ic('plus', 'width:14px;height:14px'){{ __('Aktivitas') }}</a></div>
            </details>
          @endforeach
          <form method="post" action="{{ route('fas.subject.store', $ag) }}" class="act">@csrf
            <input type="text" name="name" required placeholder="{{ __('Nama mata pelatihan baru') }}" style="flex:1;min-width:160px"><button class="btn sm">@ic('plus', 'width:14px;height:14px'){{ __('Mata pelatihan') }}</button></form>
        </details>
      @empty
        <div class="empty">{{ __('Mulai dengan menambah agenda pertama.') }}</div>
      @endforelse
    </div>
    <form method="post" action="{{ route('fas.agenda.store', $t) }}" class="row">@csrf
      <input type="text" name="name" required placeholder="{{ __('Nama agenda baru, contoh: Agenda 1 · Sikap Perilaku Bela Negara') }}" style="flex:1;min-width:200px"><button class="btn primary">@ic('plus'){{ __('Agenda') }}</button></form>
  </div>

  <div class="stack">
    <form method="post" action="{{ route('fas.training.update', $t) }}" class="card stack">@csrf @method('put')
      <h3>{{ __('Pengaturan pelatihan') }}</h3>
      <div class="field"><label>{{ __('Nama') }}</label><input type="text" name="name" value="{{ $t->name }}" required></div>
      <div class="field"><label>{{ __('Jenis') }}</label><select name="type">@foreach (\App\Models\Training::TYPES as $k => $v)<option value="{{ $k }}" @selected($t->type === $k)>{{ __($v) }}</option>@endforeach</select></div>
      <div class="g2 grid" style="gap:8px">
        <div class="field"><label>{{ __('Dibuka') }}</label><input type="datetime-local" name="opens_at" value="{{ $t->opens_at?->format('Y-m-d\TH:i') }}"></div>
        <div class="field"><label>{{ __('Ditutup') }}</label><input type="datetime-local" name="closes_at" value="{{ $t->closes_at?->format('Y-m-d\TH:i') }}"></div></div>
      <label class="sw"><input type="checkbox" name="use_cohorts" value="1" @checked($t->use_cohorts)> {{ __('Gunakan angkatan') }}</label>
      <div class="field"><label>{{ __('Cara peserta masuk') }}</label><select name="access_mode">@foreach (\App\Services\Enroll::MODES as $k => $v)<option value="{{ $k }}" @selected($t->access_mode === $k)>{{ __($v) }}</option>@endforeach</select>
        <span class="hint">{{ __('Peserta yang diundang (email atau WhatsApp) atau ada di daftar Excel selalu langsung aktif.') }}</span></div>
      <button class="btn primary">{{ __('Simpan') }}</button>
    </form>
    <div class="ai-box"><div class="ai-head">@ic('lock'){{ __('Cara kerja kunci') }}</div>
      <div class="stack" style="font-size:13px">
        <div>{{ __('Peserta hanya bisa membuka item yang jadwalnya sedang berjalan.') }}</div>
        <div>{{ __('Video, audio, dan ebook wajib dituntaskan sebelum aktivitas berikutnya terbuka.') }}</div>
        <div>{{ __('Kuis dianggap selesai bila nilainya mencapai batas lulus.') }}</div></div></div>
    <form method="post" action="{{ route('fas.training.destroy', $t) }}" onsubmit="return confirm('{{ __('Hapus pelatihan ini beserta semua isi dan jawaban peserta?') }}')">@csrf @method('delete')
      <button class="btn sm ghost" style="color:var(--bad)">@ic('trash', 'width:14px;height:14px'){{ __('Hapus pelatihan') }}</button></form>
  </div>
</div>
@endsection
