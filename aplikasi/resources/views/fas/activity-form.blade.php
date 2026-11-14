@extends('layouts.app')
@section('title', $a->exists ? $a->title : __('Aktivitas baru'))
@section('content')
@php $t = $subject->agenda->training; $c = fn ($k, $d = '') => old('cfg.'.$k, $a->cfg($k, $d)); @endphp
<div class="crumb"><a href="{{ route('fas.training.show', $t) }}">{{ $t->name }}</a>›<span>{{ $subject->agenda->name }}</span>›<b>{{ $subject->name }}</b></div>
<div class="head"><div><h1>{{ $a->exists ? __('Ubah aktivitas') : __('Aktivitas baru') }}</h1><p class="muted">{{ __('Pilih jenis, isi petunjuk, lalu atur aturannya.') }}</p></div>
  @if ($a->exists)<a class="btn" href="{{ route('pes.activity', $a) }}">@ic('play'){{ __('Coba sebagai peserta') }}</a>@endif</div>

<form method="post" action="{{ $a->exists ? route('fas.activity.update', $a) : route('fas.activity.store', $subject) }}" id="actForm" class="split" enctype="multipart/form-data">@csrf @if ($a->exists) @method('put') @endif
  <div class="stack">
    <div class="card stack">
      <div class="field"><label>{{ __('Jenis aktivitas') }}</label>
        <div class="qtype" id="typePick">@foreach (\App\Models\Activity::TYPES as $k => [$lbl, $icn])
          <label><input type="radio" name="type" value="{{ $k }}" @checked(old('type', $a->type) === $k)> {{ __($lbl) }}</label>@endforeach</div></div>
      <div class="field"><label for="title">{{ __('Judul') }}</label><input id="title" type="text" name="title" value="{{ old('title', $a->title) }}" required></div>
      <div class="field"><label>{{ __('Petunjuk atau isi materi') }}</label>
        @include('partials.editor', ['id' => 'edIns', 'name' => 'instructions', 'html' => old('instructions', $a->instructions ?? '')])</div>
      <div class="row"><button type="button" class="btn ai" onclick="ghoDesign()">@ic('spark'){{ __('Bantu rancang') }}</button><span class="faint" style="font-size:12px">{{ __('Tulis tujuan singkat di judul, lalu tekan tombol ini untuk dibuatkan petunjuk dan rubrik.') }}</span></div>
    </div>

    <div class="card stack cfg" data-for="video audio ebook">
      <h3>{{ __('Media') }}</h3>
      <div class="field" data-for="ebook"><label>{{ __('Tautan') }}</label><input type="url" name="cfg[url]" value="{{ $c('url') }}" placeholder="https://">
        <span class="hint">{{ __('Tautan flipbook Heyzine, contoh https://heyzine.com/flip-book/xxxx.html') }}</span></div>
      <div class="grid g2" data-for="video">
        <div class="field"><label>{{ __('Tautan YouTube atau video') }}</label><input type="url" name="video_url" value="{{ $a->type === 'video' && ! str_starts_with((string) $c('url'), 'materi/') ? $c('url') : '' }}" placeholder="https://">
          <span class="hint">{{ __('Tautan YouTube atau file MP4. Peserta tidak bisa melompat ke bagian yang belum ditonton.') }}</span></div>
        <div class="field"><label>{{ __('Atau unggah berkas video') }}</label><input type="file" name="video_file" accept="video/*">
          @if ($a->type === 'video' && str_starts_with((string) $c('url'), 'materi/'))<span class="hint">{{ __('Sudah ada berkas tersimpan. Unggah berkas baru untuk menggantinya.') }}</span>@endif</div>
      </div>
      <div class="grid g2" data-for="audio">
        <div class="field"><label>{{ __('Tautan file MP3') }}</label><input type="url" name="audio_url" value="{{ $a->type === 'audio' && ! str_starts_with((string) $c('url'), 'materi/') ? $c('url') : '' }}" placeholder="https://">
          <span class="hint">{{ __('Peserta tidak bisa melompat ke depan.') }}</span></div>
        <div class="field"><label>{{ __('Atau unggah berkas MP3') }}</label><input type="file" name="audio_file" accept="audio/*">
          @if ($a->type === 'audio' && str_starts_with((string) $c('url'), 'materi/'))<span class="hint">{{ __('Sudah ada berkas tersimpan. Unggah berkas baru untuk menggantinya.') }}</span>@endif</div>
      </div>
      <div class="grid g2" data-for="ebook">
        <div class="field"><label>{{ __('Jumlah halaman') }}</label><input type="number" min="1" name="cfg[pages]" value="{{ $c('pages', 10) }}"></div>
        <div class="field"><label>{{ __('Minimal detik per halaman') }}</label><input type="number" min="0" name="cfg[seconds_per_page]" value="{{ $c('seconds_per_page', 20) }}"></div></div>
      <div class="grid g2" data-for="audio">
        <div class="field"><label>{{ __('Tautan gambar sampul') }}</label><input type="url" name="cfg[cover_url]" value="{{ $c('cover_url') }}" placeholder="https://"></div>
        <div class="field"><label>{{ __('Atau unggah gambar sampul') }}</label><input type="file" name="cover_file" accept="image/*">
          @if ($c('cover'))<span class="hint">{{ __('Sudah ada gambar tersimpan. Unggah gambar baru untuk menggantinya.') }}</span>@endif</div>
      </div>
      @php $fr = array_merge(\App\Support\Frame::defaults(), (array) $a->cfg('frame', [])); $ratioLabels = ['16:9' => '16:9 (' . __('Layar lebar') . ')', '4:3' => '4:3', '1:1' => '1:1 (' . __('Persegi') . ')', '9:16' => '9:16 (' . __('Vertikal') . ')', '21:9' => '21:9 (' . __('Ultra-lebar') . ')', 'custom' => __('Ukuran sendiri (piksel)')]; @endphp
      <div class="grid g2" data-for="video audio ebook">
        <div class="field"><label>{{ __('Ukuran tampilan') }}</label>
          <select id="frRatio" name="cfg[frame][ratio]">
            @foreach ($ratioLabels as $rv => $rl)<option value="{{ $rv }}" @selected($fr['ratio'] === $rv)>{{ $rl }}</option>@endforeach
          </select>
          <span class="hint" data-for="video ebook">{{ __('Lebar selalu menyesuaikan layar, tinggi mengikuti rasio ini. Pakai 1920x1080 untuk ukuran layar standar.') }}</span>
          <span class="hint" data-for="audio">{{ __('Gambar sampul mengisi penuh kotak ini sesuai rasio (dipotong bila perlu), tidak ada bilah hitam di sisinya. Pakai 9:16 untuk sampul tegak seperti poster.') }}</span></div>
        <div class="row" id="frCustom" @if($fr['ratio'] !== 'custom') hidden @endif>
          <div class="field"><label>{{ __('Lebar (px)') }}</label><input type="number" min="1" name="cfg[frame][w]" value="{{ $fr['w'] }}"></div>
          <div class="field"><label>{{ __('Tinggi (px)') }}</label><input type="number" min="1" name="cfg[frame][h]" value="{{ $fr['h'] }}"></div></div>
      </div>
    </div>

    <div class="card stack cfg" data-for="tugas refleksi studi_kasus formulir">
      <h3>{{ __('Penilaian') }}</h3>
      <div class="field"><label>{{ __('Rubrik (satu kriteria per baris, dengan bobot)') }}</label><textarea name="cfg[rubric]" placeholder="{{ __('Ketepatan isi 40%') }}">{{ $c('rubric') }}</textarea></div>
      <div class="grid g2" data-for="tugas refleksi"><div class="field"><label>{{ __('Minimal karakter jawaban') }}</label><input type="number" min="0" name="cfg[min_chars]" value="{{ $c('min_chars', 0) }}"></div></div>
      <label class="sw" data-for="tugas refleksi"><input type="checkbox" name="cfg[hold_feedback]" value="1" @checked($c('hold_feedback'))> {{ __('Tahan tanggapan sampai saya setujui') }}</label>
    </div>

    <div class="card stack cfg" data-for="studi_kasus">
      <h3>{{ __('Studi kasus interaktif') }}</h3>
      <div class="grid g2">
        <div class="field"><label>{{ __('Jumlah putaran') }}</label><input type="number" min="1" max="8" name="cfg[rounds]" value="{{ $c('rounds', 3) }}"></div>
        <div class="field"><label>{{ __('Gaya tanggapan') }}</label><input type="text" name="cfg[style]" value="{{ $c('style', 'menyemangati, tetap kritis') }}"></div></div>
      <div class="field"><label>{{ __('Jenis perubahan kondisi') }}</label><input type="text" name="cfg[disruptions]" value="{{ $c('disruptions') }}" placeholder="{{ __('SDM berkurang, anggaran dipotong, regulasi baru, isu viral') }}"></div>
      <div class="field"><label>{{ __('Situasi awal (kosongkan agar dibuatkan dari petunjuk)') }}</label><textarea name="cfg[opening]">{{ $c('opening') }}</textarea></div>
      <div class="field"><label>{{ __('Pertanyaan pertama') }}</label><input type="text" name="cfg[opening_question]" value="{{ $c('opening_question') }}"></div>
    </div>


    <div class="card stack cfg" data-for="formulir">
      @php $P = \App\Support\Page::class; $pg = array_merge($P::defaults(), (array) $a->cfg('page', [])); $fo = array_merge($P::fontDefaults(), (array) $a->cfg('fonts', [])); @endphp
      <div class="row" style="justify-content:space-between"><h3>{{ __('Isian untuk peserta') }}</h3>
        <span class="row"><select id="fbTpl" style="width:auto"><option value="">{{ __('Pakai templat…') }}</option>@foreach ($templates as $tp)<option value="{{ $tp->id }}">{{ $tp->name }}</option>@endforeach</select>
          <button type="button" class="btn sm" id="fbSaveTpl">{{ __('Simpan sebagai templat') }}</button></span></div>
      <p class="faint" style="font-size:12.5px">{{ __('Tambah, hapus, dan urutkan bagian serta isian. Setiap isian punya kode yang bisa dipakai di perintah output.') }}</p>
      <div id="fbForm" class="stack"></div>
      <div><button type="button" class="btn" id="fbAddSec">@ic('plus'){{ __('Bagian isian') }}</button></div>
      <h3 style="margin-top:8px">{{ __('Output dokumen') }}</h3>
      <p class="faint" style="font-size:12.5px">{{ __('Setiap bagian output disusun dari isian peserta sesuai perintahnya. Bagian bisa ditambah dan dihapus.') }}</p>
      <div id="fbOut" class="stack"></div>
      <div><button type="button" class="btn" id="fbAddOut">@ic('plus'){{ __('Bagian output') }}</button></div>
      <div class="grid g2" style="margin-top:8px">
        <div class="stack" id="fbPage"><h3>{{ __('Templat halaman') }}</h3>
          <div class="grid g2" style="gap:8px">
            <div class="field"><label>{{ __('Ukuran kertas') }}</label><select name="size">@foreach (array_keys($P::SIZES) as $sz)<option value="{{ $sz }}" @selected($pg['size'] === $sz)>{{ $sz === 'custom' ? __('Ukuran bebas') : $sz.($sz === 'F4' ? ' (Folio)' : '') }}</option>@endforeach</select></div>
            <div class="field"><label>{{ __('Orientasi') }}</label><select name="orientation"><option value="portrait">{{ __('Tegak') }}</option><option value="landscape" @selected($pg['orientation'] === 'landscape')>{{ __('Mendatar') }}</option></select></div></div>
          <div class="grid g2" style="gap:8px" id="fbCustom">
            <div class="field"><label>{{ __('Lebar (mm)') }}</label><input type="number" name="width_mm" value="{{ $pg['width_mm'] }}"></div>
            <div class="field"><label>{{ __('Tinggi (mm)') }}</label><input type="number" name="height_mm" value="{{ $pg['height_mm'] }}"></div></div>
          <div class="grid g4" style="gap:6px">
            @foreach (['top' => __('Atas'), 'right' => __('Kanan'), 'bottom' => __('Bawah'), 'left' => __('Kiri')] as $mk => $ml)
              <div class="field"><label style="font-size:12px">{{ $ml }} (mm)</label><input type="number" min="0" name="m_{{ $mk }}" value="{{ $pg['margin'][$mk] ?? 25 }}"></div>@endforeach</div>
          <div class="row"><div id="fbPv" style="background:#fff;border:1px solid var(--line);box-shadow:var(--shadow);box-sizing:border-box"><div style="height:100%;border:1px dashed #cbd5e1;color:#94a3b8;font-size:9px;padding:3px">Aa</div></div><span class="faint mono" id="fbPvLbl"></span></div>
        </div>
        <div class="stack" id="fbFonts"><h3>{{ __('Huruf') }}</h3>
          <div class="field"><label>{{ __('Huruf isian peserta') }}</label><div class="row" style="flex-wrap:nowrap"><select name="input_family">@foreach (array_keys($P::FONTS) as $fn)<option @selected($fo['input_family'] === $fn)>{{ $fn }}</option>@endforeach</select><input type="number" name="input_size" min="9" max="24" value="{{ $fo['input_size'] }}" style="width:80px"></div></div>
          <div class="field"><label>{{ __('Huruf output') }}</label><div class="row" style="flex-wrap:nowrap"><select name="output_family">@foreach (array_keys($P::FONTS) as $fn)<option @selected($fo['output_family'] === $fn)>{{ $fn }}</option>@endforeach</select><input type="number" name="output_size" min="8" max="20" value="{{ $fo['output_size'] }}" style="width:80px"></div></div>
          <div class="grid g2" style="gap:8px"><div class="field"><label>{{ __('Ukuran judul output') }}</label><input type="number" name="heading_size" min="8" max="28" value="{{ $fo['heading_size'] }}"></div>
            <div class="field"><label>{{ __('Spasi baris') }}</label><select name="line_height">@foreach ([1, 1.15, 1.5, 2] as $lh)<option value="{{ $lh }}" @selected((float) $fo['line_height'] === (float) $lh)>{{ $lh }}</option>@endforeach</select></div></div>
        </div>
      </div>
      <input type="hidden" name="cfg[form]" id="cfgForm"><input type="hidden" name="cfg[output]" id="cfgOutput"><input type="hidden" name="cfg[page]" id="cfgPage"><input type="hidden" name="cfg[fonts]" id="cfgFonts">
      <label class="sw"><input type="checkbox" name="cfg[hold_feedback]" value="1" @checked($c('hold_feedback'))> {{ __('Dokumen ditinjau dulu sebelum dianggap selesai') }}</label>
    </div>
    <div class="card stack cfg" data-for="kuis">
      <h3>{{ __('Kuis') }}</h3>
      <div class="grid g2"><div class="field"><label>{{ __('Batas lulus (0–100)') }}</label><input type="number" min="0" max="100" name="cfg[passing]" value="{{ $c('passing', 70) }}"></div></div>
      @unless ($a->exists)<p class="faint" style="font-size:12.5px">{{ __('Simpan dulu, lalu tambahkan soal di bawah.') }}</p>@endunless
    </div>
  </div>

  <div class="stack">
    <div class="card stack">
      <h3>{{ __('Aturan') }}</h3>
      <div class="field"><label>{{ __('Dikelola oleh') }}</label><select name="owner_role"><option value="fasilitator">{{ __('Fasilitator') }}</option><option value="coach" @selected(old('owner_role', $a->owner_role) === 'coach')>{{ __('Coach') }}</option></select>
        <span class="hint">{{ __('Coach: rancangan aktualisasi, aksi perubahan, proyek perubahan, learning journal, lesson learned.') }}</span></div>
      <label class="sw"><input type="checkbox" name="required" value="1" @checked(old('required', $a->required ?? true))> {{ __('Wajib diselesaikan') }}</label>
      <label class="sw"><input type="checkbox" name="sequential" value="1" @checked(old('sequential', $a->sequential ?? true))> {{ __('Berurutan (aktivitas sebelumnya harus selesai)') }}</label>
      <label class="sw cfg" data-for="tugas refleksi"><input type="checkbox" name="allow_upload" value="1" @checked(old('allow_upload', $a->allow_upload))> {{ __('Izinkan unggah dokumen') }}</label>
      <div class="stack cfg" data-for="tugas refleksi studi_kasus formulir" style="gap:8px;border-top:1px solid var(--line);padding-top:10px">
        <b style="font-size:13px">{{ __('Batas jawaban (hemat kuota)') }}</b>
        <label class="sw"><input type="checkbox" name="cfg[allow_paste]" value="1" @checked($c('allow_paste', true))> {{ __('Boleh tempel (copy-paste) teks dari luar') }}</label>
        <div class="grid g2" style="gap:8px">
          <div class="field"><label>{{ __('Maks. karakter per isian') }}</label><input type="number" min="0" name="cfg[max_chars]" value="{{ $c('max_chars', 0) }}"><span class="hint">{{ __('0 = tanpa batas') }}</span></div>
          <div class="field"><label>{{ __('Maks. lama rekaman suara (detik)') }}</label><input type="number" min="0" max="600" name="cfg[max_voice]" value="{{ $c('max_voice', 60) }}"><span class="hint">{{ __('0 = matikan bicara') }}</span></div></div>
      </div>
      <div class="grid g2" style="gap:8px">
        <div class="field"><label>{{ __('Dibuka') }}</label><input type="datetime-local" name="opens_at" value="{{ old('opens_at', $a->opens_at?->format('Y-m-d\TH:i')) }}"></div>
        <div class="field"><label>{{ __('Ditutup') }}</label><input type="datetime-local" name="closes_at" value="{{ old('closes_at', $a->closes_at?->format('Y-m-d\TH:i')) }}"></div></div>
      <button class="btn primary">@ic('check'){{ __('Simpan aktivitas') }}</button>
    </div>
  </div>
</form>

@if ($a->exists && $a->type === 'kuis')
  <div class="card stack" id="quizCard">
    <div class="row" style="justify-content:space-between"><h2>{{ __('Soal kuis') }}</h2><span class="faint" id="qCount"></span></div>
    <details class="box"><summary>@ic('spark', 'width:14px;height:14px;vertical-align:-2px') {{ __('Buat soal dari materi') }}</summary>
      <div class="stack" style="margin-top:10px">
        <div class="field"><label>{{ __('Tempel materi') }}</label><textarea id="genMat" style="min-height:140px" placeholder="{{ __('Tempel teks modul atau ringkasan materi di sini (minimal beberapa paragraf).') }}"></textarea></div>
        <div class="formgrid">
          <div class="field"><label>{{ __('Jumlah soal') }}</label><input type="number" id="genN" min="1" max="30" value="10"></div>
          <div class="field"><label>{{ __('Tingkat') }}</label><select id="genLv"><option value="campuran">{{ __('Campuran') }}</option><option value="mudah">{{ __('Mudah') }}</option><option value="sedang">{{ __('Sedang') }}</option><option value="sulit (HOTS)">{{ __('Sulit (HOTS)') }}</option></select></div></div>
        <div class="qtype" id="genTypes">@foreach (\App\Models\QuizQuestion::TYPES as $k => $v)<label><input type="checkbox" value="{{ $k }}" checked> {{ __($v) }}</label>@endforeach</div>
        <div><button type="button" class="btn ai" onclick="ghoGenQuiz('{{ route('fas.activity.genquiz', $a) }}')">@ic('spark'){{ __('Buatkan soal') }}</button></div>
      </div></details>
    <div id="qList" class="stack"></div>
    <div class="row">
      <select id="qAddType" style="width:auto">@foreach (\App\Models\QuizQuestion::TYPES as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach</select>
      <button type="button" class="btn" onclick="ghoQuiz.add(document.getElementById('qAddType').value)">@ic('plus'){{ __('Tambah soal') }}</button></div>
    <form method="post" action="{{ route('fas.activity.questions', $a) }}" onsubmit="this.questions.value=JSON.stringify(ghoQuiz.items)">@csrf
      <input type="hidden" name="questions"><button class="btn primary">@ic('check'){{ __('Simpan semua soal') }}</button></form>
  </div>
  @php
    $qs = $a->questions->map(fn ($q) => array_merge(['type' => $q->type, 'prompt' => $q->prompt, 'explanation' => $q->explanation], $q->data ?? [], $q->answer ?? []))->values();
  @endphp
  @push('scripts')<script>ghoQuiz.init(document.getElementById('qList'), {{ \Illuminate\Support\Js::from($qs) }}, {{ \Illuminate\Support\Js::from(\App\Models\QuizQuestion::TYPES) }});</script>@endpush
@endif

@push('scripts')
<script src="{{ asset('js/gho-form.js') }}?v=1"></script>
<script>
ghoFormBuilder.init({form: {{ \Illuminate\Support\Js::from($a->cfg('form', ['sections' => []])) }}, output: {{ \Illuminate\Support\Js::from($a->cfg('output', ['sections' => []])) }},
  types: {{ \Illuminate\Support\Js::from(array_map('__', \App\Support\Page::FIELD_TYPES)) }}, sizes: {{ \Illuminate\Support\Js::from(\App\Support\Page::SIZES) }}, fonts: {{ \Illuminate\Support\Js::from(\App\Support\Page::FONTS) }},
  templates: {{ \Illuminate\Support\Js::from($templates->map->only(['id', 'name', 'config'])) }}, tplUrl: {{ \Illuminate\Support\Js::from(route('fas.template.store')) }},
  t: {replace: {{ \Illuminate\Support\Js::from(__('Ganti isian dan output dengan templat ini?')) }}, loaded: {{ \Illuminate\Support\Js::from(__('Templat dimuat. Periksa lalu simpan.')) }}, tplName: {{ \Illuminate\Support\Js::from(__('Nama templat:')) }}, tplSaved: {{ \Illuminate\Support\Js::from(__('Templat disimpan.')) }}}});
</script>
<script>
(function(){
  const form = document.getElementById('actForm');
  const sync = () => { const v = (form.querySelector('[name=type]:checked')||{}).value;
    form.querySelectorAll('[data-for]').forEach(el => { el.hidden = !el.dataset.for.split(' ').includes(v); }); };
  form.querySelectorAll('[name=type]').forEach(r => r.addEventListener('change', sync)); sync();
  const frRatio = document.getElementById('frRatio'), frCustom = document.getElementById('frCustom');
  if (frRatio) { const syncFr = () => { frCustom.hidden = frRatio.value !== 'custom'; }; frRatio.addEventListener('change', syncFr); syncFr(); }
  window.ghoDesign = async () => {
    const type = (form.querySelector('[name=type]:checked')||{}).value, goal = form.title.value.trim();
    if (goal.length < 10) { ghoToast({{ \Illuminate\Support\Js::from(__('Tulis tujuan atau judul yang lebih jelas dulu (minimal 10 huruf).')) }}); return; }
    ghoToast({{ \Illuminate\Support\Js::from(__('Sedang menyusun…')) }});
    const r = await ghoPost({{ \Illuminate\Support\Js::from(route('fas.design')) }}, {type, goal, training: {{ \Illuminate\Support\Js::from($t->name) }}});
    if (r.error) { ghoToast(r.error); return; }
    if (r.title) form.title.value = r.title;
    if (r.instructions) ghoEditorSet('edIns', r.instructions.split(/\n{2,}/).map(p => '<p>'+ghoEsc(p).replace(/\n/g,'<br>')+'</p>').join(''));
    if (r.rubric && form['cfg[rubric]']) form['cfg[rubric]'].value = r.rubric;
    ghoToast({{ \Illuminate\Support\Js::from(__('Rancangan dimasukkan. Periksa lalu simpan.')) }});
  };
})();
</script>
@endpush
@endsection
