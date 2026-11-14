@php
    $u = auth()->user();
    $role = session('role', 'peserta');
    $menus = [
        'coach' => [
            [__('Bimbingan'), [
                ['coach.home', 'home', __('Beranda coach')],
                ['coach.review', 'inbox', __('Tinjau jawaban')],
            ]],
        ],
        'fasilitator' => [
            [__('Kelas'), [
                ['fas.home', 'layers', __('Pelatihan saya')],
                ['fas.review', 'inbox', __('Tinjau jawaban')],
            ]],
        ],
        'cms' => [
            [__('Media sosial'), [
                ['cms.home', 'home', __('Beranda CMS')],
                ['cms.create', 'send', __('Terbitkan konten')],
                ['cms.accounts', 'users', __('Akun terhubung')],
            ]],
        ],
        'peserta' => [
            [__('Belajar'), [
                ['pes.home', 'book', __('Pelatihan saya')],
            ]],
        ],
    ];
    $groups = $menus[$role] ?? $menus['peserta'];
    if ($u && in_array('admin', $u->roles ?? [], true)) {
        $groups[] = [__('Pengaturan'), [
            ['admin.providers', 'gear', __('Layanan cerdas')],
            ['admin.users', 'users', __('Pengguna')],
            ['admin.settings', 'chat', __('WhatsApp')],
        ]];
    }
    $flat = collect($groups)->flatMap(fn ($g) => $g[1])->values();
    $roleNames = ['coach' => __('Coach'), 'fasilitator' => __('Fasilitator'), 'cms' => __('CMS'), 'peserta' => __('Peserta')];
    $isOn = fn ($r) => request()->routeIs($r) || request()->routeIs(\Illuminate\Support\Str::beforeLast($r, '.').'.'.\Illuminate\Support\Str::afterLast($r, '.').'*');
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Gho Studio') · Gho Studio</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/gho.css') }}?v=1">
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="logo"><div class="logo-mark">G</div><div><b>Gho Studio</b><small>{{ $roleNames[$role] ?? '' }}</small></div></div>
    <nav class="nav" aria-label="{{ __('Menu utama') }}">
      @foreach ($groups as [$label, $items])
        <div class="grp">{{ $label }}</div>
        @foreach ($items as [$r, $icon, $text])
          <a href="{{ route($r) }}" class="{{ request()->routeIs($r) ? 'on' : '' }}">@ic($icon){{ $text }}</a>
        @endforeach
      @endforeach
    </nav>
    <div class="side-foot">
      <form method="post" action="{{ route('logout') }}">@csrf<button class="btn sm ghost" style="width:100%">@ic('out'){{ __('Keluar') }}</button></form>
    </div>
  </aside>

  <div class="main">
    <header class="top">
      <div class="mlogo"><div class="logo-mark">G</div>Gho Studio</div>
      @php $sw = $u->switchableRoles(); @endphp
      @if (count($sw) > 1)
        <div class="roles" role="tablist" aria-label="{{ __('Pilih peran') }}">
          @foreach ($sw as $r)
            <button type="button" class="{{ $role === $r ? 'on' : '' }}" onclick="location.href='{{ route('role.switch', $r) }}'">{{ $roleNames[$r] }}</button>
          @endforeach
        </div>
      @endif
      <div class="top-right">
        <div class="saved" id="saved" hidden><i></i><span>{{ __('Tersimpan') }}</span></div>
        <a class="btn sm ghost" href="{{ route('locale', app()->getLocale() === 'id' ? 'en' : 'id') }}" title="{{ __('Ganti bahasa') }}">@ic('globe'){{ app()->getLocale() === 'id' ? 'EN' : 'ID' }}</a>
        <div class="avatar" title="{{ $u->name }}">{{ \Illuminate\Support\Str::of($u->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</div>
      </div>
    </header>

    <main class="content">
      @if (session('ok'))<div class="chip good" style="align-self:flex-start;padding:8px 12px;font-size:13px;white-space:normal" role="status">✓ {{ session('ok') }}</div>@endif
      @if (session('err'))<div class="chip bad" style="align-self:flex-start;padding:8px 12px;font-size:13px;white-space:normal" role="alert">{{ session('err') }}</div>@endif
      @if ($errors->any())<div class="chip bad" style="align-self:flex-start;padding:8px 12px;font-size:13px;white-space:normal" role="alert">{{ $errors->first() }}</div>@endif
      @yield('content')
    </main>
  </div>
</div>

<nav class="bnav" aria-label="{{ __('Menu') }}">
  @foreach ($flat->take(count($flat) > 4 ? 3 : 4) as [$r, $icon, $text])
    <a href="{{ route($r) }}" class="{{ request()->routeIs($r) ? 'on' : '' }}">@ic($icon)<span>{{ $text }}</span></a>
  @endforeach
  <button type="button" onclick="document.getElementById('more').hidden=false">@ic('more')<span>{{ __('Lainnya') }}</span></button>
</nav>
<div class="sheet" id="more" hidden onclick="if(event.target===this)this.hidden=true">
  <div class="pane"><nav class="nav">
    @foreach ($groups as [$label, $items])
      <div class="grp">{{ $label }}</div>
      @foreach ($items as [$r, $icon, $text])<a href="{{ route($r) }}">@ic($icon){{ $text }}</a>@endforeach
    @endforeach
    <div class="grp">{{ __('Akun') }}</div>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn ghost" style="width:100%;justify-content:flex-start;border:0">@ic('out'){{ __('Keluar') }}</button></form>
  </nav></div>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.GHO = {csrf: document.querySelector('meta[name=csrf-token]').content, t: {{ \Illuminate\Support\Js::from(['saving' => __('Menyimpan…'), 'saved' => __('Tersimpan'), 'fail' => __('Gagal menyimpan, coba lagi'), 'words' => __('kata')]) }}};</script>
<script src="{{ asset('js/gho.js') }}?v=1"></script>
@stack('scripts')
</body>
</html>
