@extends('layouts.app')
@section('title', __('Layanan cerdas'))
@section('content')
<div class="head"><div><h1>{{ __('Layanan cerdas') }}</h1><p class="muted">{{ __('Pilih penyedia yang menjalankan tanggapan, studi kasus, dan pembuatan soal. Kunci API disimpan terenkripsi dan tidak pernah tampil di browser peserta.') }}</p></div></div>
<div class="split">
  <div class="stack">
    @forelse ($providers as $p)
      @php $u = $usage[$p->id] ?? null; @endphp
      <details class="box" @if ($loop->first && $providers->count() === 1) open @endif>
        <summary><span class="row" style="display:inline-flex"><b>{{ $p->label }}</b> <span class="mono faint">{{ $p->model }}</span>
          @if ($p->is_default)<span class="chip good">{{ __('Utama') }}</span>@endif @unless ($p->enabled)<span class="chip">{{ __('Nonaktif') }}</span>@endunless
          <span class="faint" style="font-size:12px">{{ __(':n permintaan 30 hari', ['n' => $u->n ?? 0]) }}@if ($u && $u->fails) · {{ __(':n gagal', ['n' => $u->fails]) }}@endif</span></span></summary>
        <form method="post" action="{{ route('admin.providers.update', $p) }}" class="stack" style="margin-top:10px">@csrf @method('put')
          @include('admin.provider-fields', ['p' => $p])
          <div class="row"><button class="btn sm primary">{{ __('Simpan') }}</button>
            <button class="btn sm" formaction="{{ route('admin.providers.test', $p) }}" formmethod="post" onclick="this.form.querySelector('[name=_method]').disabled=true">{{ __('Uji') }}</button>
            @unless ($p->is_default)<button class="btn sm" formaction="{{ route('admin.providers.default', $p) }}" onclick="this.form.querySelector('[name=_method]').disabled=true">{{ __('Jadikan utama') }}</button>@endunless
            <button class="btn sm ghost" style="color:var(--bad)" formaction="{{ route('admin.providers.destroy', $p) }}" onclick="if(!confirm({{ \Illuminate\Support\Js::from(__('Hapus penyedia ini?')) }}))return false;this.form.querySelector('[name=_method]').value='delete'">{{ __('Hapus') }}</button></div>
        </form>
      </details>
    @empty
      <div class="empty">{{ __('Belum ada penyedia. Tambahkan minimal satu di kanan, misalnya DeepSeek.') }}</div>
    @endforelse
  </div>
  <form method="post" action="{{ route('admin.providers.store') }}" class="card stack" id="newProv">@csrf
    <h3>{{ __('Tambah penyedia') }}</h3>
    <div class="field"><label>{{ __('Pilih cepat') }}</label><select id="preset">@foreach ($presets as $k => $v)<option value="{{ $k }}" data-v="{{ json_encode($v) }}">{{ __($v['label']) }}</option>@endforeach</select></div>
    @include('admin.provider-fields', ['p' => new \App\Models\AiProvider(\App\Models\AiProvider::PRESETS['deepseek'] + ['enabled' => true, 'max_tokens' => 4000])])
    <button class="btn primary">{{ __('Tambahkan') }}</button>
  </form>
</div>
@push('scripts')<script>
document.getElementById('preset').addEventListener('change', e => { const v = JSON.parse(e.target.selectedOptions[0].dataset.v), f = document.getElementById('newProv');
  ['label','kind','base_url','model'].forEach(k => { if (f[k]) f[k].value = v[k] || ''; }); });
</script>@endpush
@endsection
