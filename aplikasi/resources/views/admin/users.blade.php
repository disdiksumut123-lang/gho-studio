@extends('layouts.app')
@section('title', __('Pengguna'))
@section('content')
@php $rn = ['admin' => __('Super Admin'), 'coach' => __('Coach'), 'fasilitator' => __('Fasilitator'), 'cms' => __('CMS'), 'peserta' => __('Peserta')]; @endphp
<div class="head"><div><h1>{{ __('Pengguna') }}</h1><p class="muted">{{ __('Atur peran setiap akun. Super Admin otomatis bisa membuka Coach, Fasilitator, dan CMS.') }}</p></div>
  <form class="row" method="get"><input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari nama atau email') }}" style="width:220px"><button class="btn">{{ __('Cari') }}</button></form></div>
<div class="split">
  <div class="card pad0"><div class="table-wrap"><table>
    <thead><tr><th>{{ __('Nama') }}</th><th>{{ __('Peran') }}</th><th></th></tr></thead>
    <tbody>@foreach ($users as $u)
      <tr><td><b>{{ $u->name }}</b><div class="faint" style="font-size:12px">{{ $u->email }}</div></td>
        <td><form method="post" action="{{ route('admin.users.update', $u) }}" class="row" id="u{{ $u->id }}">@csrf @method('put')
          @foreach ($rn as $k => $v)<label style="font-size:12.5px"><input type="checkbox" name="roles[]" value="{{ $k }}" @checked(in_array($k, $u->roles ?? []))> {{ $v }}</label>@endforeach</form></td>
        <td><button class="btn sm" form="u{{ $u->id }}">{{ __('Simpan') }}</button></td></tr>
    @endforeach</tbody></table></div>
    @if ($users->hasPages())<div class="pager">@if ($users->previousPageUrl())<a class="btn sm" href="{{ $users->previousPageUrl() }}">‹</a>@else<span></span>@endif @if ($users->nextPageUrl())<a class="btn sm" href="{{ $users->nextPageUrl() }}">›</a>@endif</div>@endif
  </div>
  <form method="post" action="{{ route('admin.users.store') }}" class="card stack">@csrf
    <h3>{{ __('Tambah pengguna') }}</h3>
    <div class="field"><label>{{ __('Nama') }}</label><input type="text" name="name" required></div>
    <div class="field"><label>{{ __('Email') }}</label><input type="email" name="email" required></div>
    <div class="field"><label>{{ __('Kata sandi awal') }}</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div>
    <div class="qtype">@foreach ($rn as $k => $v)<label><input type="checkbox" name="roles[]" value="{{ $k }}" @checked($k === 'peserta')> {{ $v }}</label>@endforeach</div>
    <button class="btn primary">{{ __('Tambahkan') }}</button>
  </form>
</div>
@endsection
