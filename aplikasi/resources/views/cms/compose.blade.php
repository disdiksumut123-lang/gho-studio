@extends('layouts.app')
@section('title', __('Terbitkan konten'))
@section('content')
@php $P = \App\Models\SocialPost::PLATFORMS; $on = old('platforms', $post->platforms ?? []); $caps = old('captions', $post->captions ?? []);
  $rs = ['terbit' => [__('Terbit'), 'good'], 'dilewati' => [__('Dilewati'), ''], 'belum_terhubung' => [__('Belum terhubung'), 'warn'], 'gagal' => [__('Gagal'), 'bad'], 'menunggu_integrasi' => [__('Menunggu integrasi'), 'warn']]; @endphp
<div class="crumb"><a href="{{ route('cms.home') }}">CMS</a>›<b>{{ $post->exists ? $post->title : __('Konten baru') }}</b></div>
<div class="head"><div><h1>{{ __('Terbitkan konten') }}</h1><p class="muted">{{ __('Unggah sekali, pilih platform, sesuaikan caption, lalu terbitkan sekarang atau jadwalkan.') }}</p></div></div>
<form method="post" enctype="multipart/form-data" action="{{ $post->exists ? route('cms.update', $post) : route('cms.store') }}" class="split" id="cmsForm">@csrf @if ($post->exists) @method('put') @endif
  <div class="stack">
    <div class="card stack">
      <div class="field"><label for="ttl">{{ __('Judul konten') }}</label><input id="ttl" type="text" name="title" value="{{ old('title', $post->title) }}" required></div>
      <label class="drop" style="margin:0">@ic('up', 'width:28px;height:28px')<b id="mname">{{ $post->media_name ?? __('Pilih video atau foto') }}</b><span class="faint" style="font-size:12.5px">{{ __('MP4, MOV, WEBM, JPG, PNG. Maksimal 500 MB.') }}</span><input type="file" name="media" accept="video/*,image/*" onchange="document.getElementById('mname').textContent=this.files[0]?.name||''"></label>
      <div class="field"><label for="cap">{{ __('Caption utama') }}</label><textarea id="cap" name="caption">{{ old('caption', $post->caption) }}</textarea></div>
      <div class="label">{{ __('Terbitkan ke') }}</div>
      <div class="grid g2" style="gap:8px">
        @foreach ($P as $k => $m)
          <label class="plat {{ in_array($k, $on) ? 'on' : '' }}">@include('partials.plat', ['p' => $k, 'size' => 30])<span><b style="font-size:13.5px">{{ $m[0] }}</b><div class="faint" style="font-size:11.5px">{{ isset($accounts[$k]) ? __('Terhubung') : __('Belum terhubung') }} · {{ $m[3] === 'video' ? __('video') : __('foto/video') }}</div></span>
            <input type="checkbox" name="platforms[]" value="{{ $k }}" @checked(in_array($k, $on)) onchange="this.closest('.plat').classList.toggle('on',this.checked)"></label>
        @endforeach
      </div>
      <div class="row"><button type="button" class="btn ai" onclick="ghoAdapt()">@ic('spark'){{ __('Sesuaikan caption tiap platform') }}</button></div>
    </div>
    <div class="card stack"><h3>{{ __('Caption per platform') }}</h3><p class="faint" style="font-size:12px">{{ __('Kosongkan untuk memakai caption utama.') }}</p>
      @foreach ($P as $k => $m)
        <div class="field" data-pl="{{ $k }}" @unless (in_array($k, $on)) hidden @endunless><label>@include('partials.plat', ['p' => $k, 'size' => 20]) {{ $m[0] }} <span class="faint" style="font-weight:500">· {{ __('maks :n karakter', ['n' => number_format($m[4], 0, ',', '.')]) }}</span></label>
          <textarea name="captions[{{ $k }}]" maxlength="{{ $m[4] }}">{{ $caps[$k] ?? '' }}</textarea></div>
      @endforeach
    </div>
  </div>
  <div class="stack">
    <div class="card stack"><h3>{{ __('Waktu terbit') }}</h3>
      <div class="field"><label>{{ __('Jadwal (opsional)') }}</label><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $post->scheduled_at?->format('Y-m-d\TH:i')) }}"></div>
      <div class="row">
        <button class="btn primary" name="action" value="sekarang">@ic('send'){{ __('Terbitkan sekarang') }}</button>
        <button class="btn" name="action" value="jadwal">@ic('cal'){{ __('Jadwalkan') }}</button>
        <button class="btn ghost" name="action" value="draf">{{ __('Simpan draf') }}</button></div></div>
    @if ($post->exists && $post->results)
      <div class="card"><h3>{{ __('Status penerbitan') }}</h3><div class="stack" style="margin-top:10px">
        @foreach ($post->results as $k => $r)
          <div class="pstat">@include('partials.plat', ['p' => $k, 'size' => 30])<span>{{ $P[$k][0] ?? $k }}<div class="faint" style="font-size:12px">{{ $r['note'] ?? '' }}</div></span><span class="chip {{ $rs[$r['status']][1] ?? '' }}">{{ $rs[$r['status']][0] ?? $r['status'] }}</span></div>
        @endforeach</div></div>
    @endif
    <div class="ai-box"><div class="ai-head">@ic('flag'){{ __('Catatan') }}</div><p style="font-size:13px">{{ __('YouTube dan TikTok hanya menerima video. Foto otomatis dilewati di dua platform itu.') }}</p></div>
  </div>
</form>
@if ($post->exists)
  <form method="post" action="{{ route('cms.destroy', $post) }}" onsubmit="return confirm('{{ __('Hapus konten ini?') }}')">@csrf @method('delete')<button class="btn sm ghost" style="color:var(--bad)">@ic('trash', 'width:14px;height:14px'){{ __('Hapus konten') }}</button></form>
@endif
@push('scripts')<script>
(function(){ const f = document.getElementById('cmsForm');
  const sync = () => f.querySelectorAll('[data-pl]').forEach(d => d.hidden = !f.querySelector('input[name="platforms[]"][value="'+d.dataset.pl+'"]').checked);
  f.querySelectorAll('input[name="platforms[]"]').forEach(c => c.addEventListener('change', sync));
  window.ghoAdapt = async () => { const caption = f.caption.value.trim(), platforms = [...f.querySelectorAll('input[name="platforms[]"]:checked')].map(c => c.value);
    if (caption.length < 10 || !platforms.length) { ghoToast({{ \Illuminate\Support\Js::from(__('Tulis caption utama dan pilih platform dulu.')) }}); return; }
    ghoToast({{ \Illuminate\Support\Js::from(__('Sedang menyesuaikan…')) }});
    const r = await ghoPost({{ \Illuminate\Support\Js::from(route('cms.adapt')) }}, {caption, platforms});
    if (r.error) { ghoToast(r.error); return; }
    Object.entries(r.captions || {}).forEach(([k, v]) => { const t = f.querySelector('[name="captions['+k+']"]'); if (t) t.value = v; });
    ghoToast({{ \Illuminate\Support\Js::from(__('Caption disesuaikan. Periksa sebelum terbit.')) }}); };
})();
</script>@endpush
@endsection
