@extends('layouts.app')
@section('title', __('Peserta'))
@section('content')
@php $pending = $enrollments->where('status', 'menunggu'); $active = $enrollments->where('status', '!=', 'menunggu'); @endphp
<div class="crumb"><a href="{{ route('fas.home') }}">{{ __('Pelatihan saya') }}</a>›<a href="{{ route('fas.training.show', $t) }}">{{ $t->name }}</a>›<b>{{ __('Peserta') }}</b></div>
<div class="head"><div><h1>{{ __('Peserta, coach, dan angkatan') }}</h1><p class="muted">{{ __('Cara masuk saat ini:') }} <b>{{ __(\App\Services\Enroll::MODES[$t->access_mode] ?? '') }}</b>. {{ __('Ubah di Pengaturan pelatihan.') }}</p></div>
  <a class="btn" href="{{ route('fas.report', $t) }}">@ic('grid'){{ __('Laporan kelas') }}</a></div>

@if ($pending->count())
  <div class="card stack" style="border-color:var(--warn)">
    <div class="row" style="justify-content:space-between"><h2>{{ __('Menunggu persetujuan') }} <span class="chip warn">{{ $pending->count() }}</span></h2>
      <form method="post" action="{{ route('fas.enroll.decide', [$pending->first(), 'semua']) }}">@csrf<button class="btn sm primary">{{ __('Setujui semua') }}</button></form></div>
    @foreach ($pending as $e)
      <div class="row" style="justify-content:space-between;border-top:1px solid var(--line);padding-top:8px"><span><b>{{ $e->user->name }}</b> <span class="faint">{{ $e->user->email }} {{ $e->user->nip ? '· '.$e->user->nip : '' }}</span></span>
        <span class="row"><form method="post" action="{{ route('fas.enroll.decide', [$e, 'setujui']) }}">@csrf<button class="btn sm primary">{{ __('Setujui') }}</button></form>
          <form method="post" action="{{ route('fas.enroll.decide', [$e, 'tolak']) }}">@csrf<button class="btn sm ghost">{{ __('Tolak') }}</button></form></span></div>
    @endforeach
  </div>
@endif

<div class="split">
  <div class="stack">
    <div class="card pad0"><div style="padding:14px 16px"><h2>{{ __('Terdaftar') }}</h2></div><div class="table-wrap"><table>
      <thead><tr><th>{{ __('Nama') }}</th><th>{{ __('Peran') }}</th>@if ($t->use_cohorts)<th>{{ __('Angkatan') }}</th>@endif<th>{{ __('Kemajuan') }}</th></tr></thead>
      <tbody>
        @forelse ($active as $e)
          <tr><td><b>{{ $e->user->name }}</b><div class="faint" style="font-size:12px">{{ $e->user->email }}{{ $e->user->whatsapp ? ' · '.$e->user->whatsapp : '' }}</div></td>
            <td><span class="chip {{ $e->status === 'ditolak' ? 'bad' : ($e->role === 'peserta' ? '' : 'brand') }}">{{ $e->status === 'ditolak' ? __('Ditolak') : __(ucfirst($e->role)) }}</span></td>
            @if ($t->use_cohorts)<td>{{ $e->cohort?->name ?? '–' }}</td>@endif
            <td>@if ($e->role === 'peserta' && $e->status === 'aktif') @php $pct = (new \App\Services\Progress($e->user, $t))->percent(); @endphp
              <a href="{{ route('fas.report.user', [$t, $e->user]) }}" class="row" style="flex-wrap:nowrap;text-decoration:none"><div class="bar" style="width:90px"><span style="width:{{ $pct }}%"></span></div><span class="mono">{{ $pct }}%</span></a>@endif</td></tr>
        @empty
          <tr><td colspan="4" class="faint">{{ __('Belum ada peserta.') }}</td></tr>
        @endforelse
      </tbody></table></div></div>

    <div class="card pad0"><div class="row" style="padding:14px 16px;justify-content:space-between"><h2>{{ __('Undangan belum dipakai') }} <span class="chip">{{ $invites->count() }}</span></h2>
      @if ($gateway && $invites->whereNotNull('whatsapp')->whereNull('sent_at')->count())<form method="post" action="{{ route('fas.invite.sendall', $t) }}">@csrf<button class="btn sm primary">@ic('send', 'width:14px;height:14px'){{ __('Kirim semua lewat WhatsApp') }}</button></form>@endif</div>
      <div class="list">
        @forelse ($invites as $i)
          <div class="li"><div class="grow"><div class="t">{{ $i->name ?: '–' }}</div><div class="s">{{ $i->email }} {{ $i->whatsapp ? '· '.$i->whatsapp : '' }} {{ $i->nip ? '· NIP '.$i->nip : '' }} {{ $i->cohort ? '· '.$i->cohort->name : '' }}</div></div>
            @if ($i->sent_at)<span class="chip good">{{ __('Terkirim') }}</span>@endif
            @if ($i->whatsapp)<a class="btn sm" target="_blank" rel="noopener" href="{{ $i->waLink() }}" title="{{ __('Buka WhatsApp dengan pesan undangan') }}">WA</a>
              @if ($gateway)<form method="post" action="{{ route('fas.invite.send', $i) }}">@csrf<button class="btn sm ghost" title="{{ __('Kirim otomatis') }}">@ic('send', 'width:14px;height:14px')</button></form>@endif @endif
            <button type="button" class="btn sm ghost" onclick="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($i->url()) }}).then(()=>ghoToast({{ \Illuminate\Support\Js::from(__('Tautan disalin')) }}))">{{ __('Salin tautan') }}</button>
            <form method="post" action="{{ route('fas.invite.delete', $i) }}">@csrf @method('delete')<button class="btn sm ghost" aria-label="{{ __('Hapus') }}">@ic('trash', 'width:14px;height:14px')</button></form></div>
        @empty
          <div class="li"><span class="faint">{{ __('Semua undangan sudah dipakai, atau belum ada undangan.') }}</span></div>
        @endforelse
      </div></div>
  </div>

  <div class="stack">
    <div class="card stack"><h3>{{ __('Kode gabung pelatihan') }}</h3><div class="code">{{ $t->join_code }}</div></div>

    <form method="post" action="{{ route('fas.invite.store', $t) }}" class="card stack">@csrf
      <h3>{{ __('Undang peserta') }}</h3>
      <div class="field"><label>{{ __('Nama') }}</label><input type="text" name="name"></div>
      <div class="grid g2" style="gap:8px"><div class="field"><label>{{ __('Email') }}</label><input type="email" name="email"></div>
        <div class="field"><label>{{ __('WhatsApp') }}</label><input type="text" inputmode="tel" name="whatsapp" placeholder="08…"></div></div>
      <div class="field"><label>{{ __('NIP') }}</label><input type="text" inputmode="numeric" name="nip"></div>
      @if ($t->use_cohorts && $t->cohorts->count())<div class="field"><label>{{ __('Angkatan') }}</label><select name="cohort_id"><option value="">–</option>@foreach ($t->cohorts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>@endif
      <button class="btn primary">{{ __('Buat undangan') }}</button>
      <p class="faint" style="font-size:12px">{{ __('Isi email atau WhatsApp, boleh keduanya.') }}</p>
    </form>

    <form method="post" action="{{ route('fas.invite.import', $t) }}" enctype="multipart/form-data" class="card stack">@csrf
      <h3>{{ __('Unggah daftar calon peserta') }}</h3>
      <p class="faint" style="font-size:12.5px">{{ __('File Excel (.xlsx) atau CSV. Kolom: Nama, NIP, Email, WhatsApp, Jabatan, Unit Kerja. Minimal Email atau WhatsApp.') }}</p>
      <input type="file" name="file" accept=".xlsx,.csv" required>
      @if ($t->use_cohorts && $t->cohorts->count())<div class="field"><label>{{ __('Masukkan ke angkatan') }}</label><select name="cohort_id"><option value="">–</option>@foreach ($t->cohorts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>@endif
      <div class="row"><button class="btn primary">@ic('up'){{ __('Unggah') }}</button><a class="btn sm ghost" href="{{ route('fas.invite.template', $t) }}">@ic('down', 'width:14px;height:14px'){{ __('Unduh templat') }}</a></div>
    </form>

    @if ($t->use_cohorts)
      <div class="card stack"><h3>{{ __('Angkatan') }}</h3>
        @foreach ($t->cohorts as $c)<div class="row" style="justify-content:space-between"><b>{{ $c->name }}</b><span class="mono chip brand">{{ $c->join_code }}</span></div>@endforeach
        <form method="post" action="{{ route('fas.cohort.store', $t) }}" class="row">@csrf<input type="text" name="name" required placeholder="{{ __('contoh: Angkatan XII') }}" style="flex:1"><button class="btn sm">{{ __('Tambah') }}</button></form></div>
    @endif

    <form method="post" action="{{ route('fas.staff.store', $t) }}" class="card stack">@csrf
      <h3>{{ __('Tambah coach atau fasilitator') }}</h3>
      <div class="field"><label>{{ __('Email akun') }}</label><input type="email" name="email" required></div>
      <div class="field"><label>{{ __('Sebagai') }}</label><select name="role"><option value="coach">{{ __('Coach') }}</option><option value="fasilitator">{{ __('Fasilitator') }}</option><option value="peserta">{{ __('Peserta') }}</option></select></div>
      <button class="btn">{{ __('Tambahkan') }}</button>
    </form>
  </div>
</div>
@endsection
