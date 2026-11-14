<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>@yield('title') · Gho Studio</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/gho.css') }}?v=1">
<style>.auth{min-height:100vh;display:grid;place-items:center;padding:24px 16px}.auth .card{width:100%;max-width:420px;padding:24px}input[type=email],input[type=password]{width:100%;border:1px solid var(--line);background:var(--surface);border-radius:10px;padding:10px 12px;outline:none}</style>
</head>
<body>
<div class="auth"><div class="card stack" style="gap:16px">
  <div class="row" style="justify-content:space-between"><div class="logo" style="padding:0"><div class="logo-mark">G</div><div><b>Gho Studio</b><small>{{ __('Coach · Fasilitator · CMS') }}</small></div></div>
    <a class="btn sm ghost" href="{{ route('locale', app()->getLocale() === 'id' ? 'en' : 'id') }}">{{ app()->getLocale() === 'id' ? 'English' : 'Indonesia' }}</a></div>
  @if ($errors->any())<div class="chip bad" style="white-space:normal;padding:8px 12px">{{ $errors->first() }}</div>@endif
  @yield('content')
</div></div>
</body>
</html>
