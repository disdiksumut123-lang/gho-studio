{{-- Formulir dinamis untuk peserta. $a, $vals, $doc --}}
@php
  $fonts = array_merge(\App\Support\Page::fontDefaults(), (array) $a->cfg('fonts', []));
  $lim = ['paste' => $a->cfg('allow_paste', true), 'chars' => (int) $a->cfg('max_chars', 0), 'voice' => (int) $a->cfg('max_voice', 60)];
  $stLabel = ['draf' => __('Draf'), 'ditinjau' => __('Ditinjau'), 'disetujui' => __('Disetujui'), 'dikembalikan' => __('Dikembalikan')];
@endphp
@if ($doc && $doc->answer_id)
  <div class="ai-box row" style="justify-content:space-between">
    <div><b>{{ __('Isian sudah dikirim.') }}</b> <span class="chip {{ ['disetujui' => 'good', 'dikembalikan' => 'bad'][$doc->status] ?? 'accent' }}">{{ $stLabel[$doc->status] ?? $doc->status }}</span>
      @if ($doc->reviewer_note)<div style="margin-top:6px;font-size:13px"><b>{{ __('Catatan peninjau:') }}</b> {{ $doc->reviewer_note }}</div>@endif</div>
    <a class="btn primary" href="{{ route('pes.doc', [$a, auth()->user()]) }}">@ic('file'){{ __('Buka dokumen') }}</a>
  </div>
@endif
<form method="post" action="{{ route('pes.form.submit', $a) }}" enctype="multipart/form-data" class="stack" id="pform" data-save="{{ route('pes.form.save', $a) }}"
  style="font-family:{{ \App\Support\Page::font($fonts['input_family']) }};font-size:{{ (int) $fonts['input_size'] }}px">@csrf
  <input type="hidden" name="values" id="pvalues">
  @foreach ($a->cfg('form.sections', []) as $sec)
    <div class="card stack">
      <h2 style="font-family:inherit">{{ $sec['title'] ?? '' }}</h2>
      @if (! empty($sec['help']))<p class="faint" style="margin-top:-6px">{{ $sec['help'] }}</p>@endif
      @foreach ($sec['fields'] ?? [] as $f)
        @php $k = $f['key']; $v = $vals[$k] ?? null; $type = $f['type'] ?? 'text'; $req = ! empty($f['required']); @endphp
        <div class="stack pf" data-k="{{ $k }}" data-type="{{ $type }}" style="gap:6px">
          <label style="font-weight:600">{{ $f['label'] ?? $k }}@if ($req) <span style="color:var(--bad)">*</span>@endif</label>
          @if (! empty($f['help']))<span class="faint" style="font-size:.88em;margin-top:-4px">{{ $f['help'] }}</span>@endif
          @switch($type)
            @case('textarea')
              @include('partials.editor', ['id' => 'pf_'.$k, 'name' => 'f_'.$k, 'html' => is_string($v) ? $v : '', 'upload' => false, 'save' => null, 'limits' => $lim])
              @break
            @case('number')
              <input type="number" step="any" value="{{ is_scalar($v) ? $v : '' }}" @if ($req) required @endif>
              @break
            @case('date')
              <input type="date" value="{{ is_scalar($v) ? $v : '' }}" @if ($req) required @endif>
              @break
            @case('select')
              <select @if ($req) required @endif><option value="">{{ __('Pilih…') }}</option>@foreach ($f['options'] ?? [] as $o)<option @selected($v === $o)>{{ $o }}</option>@endforeach</select>
              @break
            @case('radio')
              @foreach ($f['options'] ?? [] as $o)<label class="opt"><input type="radio" name="r_{{ $k }}" value="{{ $o }}" @checked($v === $o)> <span>{{ $o }}</span></label>@endforeach
              @break
            @case('checkbox')
              @foreach ($f['options'] ?? [] as $o)<label class="opt"><input type="checkbox" value="{{ $o }}" @checked(in_array($o, (array) $v, true))> <span>{{ $o }}</span></label>@endforeach
              @break
            @case('table')
              @php $cols = $f['columns'] ?? []; $rows = is_array($v) && $v ? $v : [array_fill(0, max(count($cols), 1), '')]; @endphp
              <div style="overflow-x:auto"><table class="bst ptable"><thead><tr>@foreach ($cols as $c)<th>{{ $c }}</th>@endforeach<th></th></tr></thead><tbody>
                @foreach ($rows as $row)<tr>@foreach ($cols as $ci => $c)<td><input type="text" value="{{ $row[$ci] ?? '' }}" aria-label="{{ $c }}"></td>@endforeach<td><button type="button" class="btn ghost sm" data-delrow aria-label="{{ __('Hapus baris') }}">×</button></td></tr>@endforeach
              </tbody></table></div>
              <div><button type="button" class="btn sm" data-addrow>+ {{ __('Tambah baris') }}</button></div>
              @break
            @case('file')
              @if (is_array($v) && ! empty($v['name']))<span class="faint" style="font-size:.88em">{{ __('Berkas tersimpan: :n', ['n' => $v['name']]) }}</span>@endif
              <input type="file" name="file_{{ $k }}" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png">
              @break
            @default
              <input type="text" value="{{ is_scalar($v) ? $v : '' }}" data-paste="{{ $lim['paste'] ? 1 : 0 }}" @if ($req) required @endif>
          @endswitch
        </div>
      @endforeach
    </div>
  @endforeach
  <div class="row"><button class="btn primary">@ic('send'){{ $doc && $doc->answer_id ? __('Kirim ulang isian') : __('Kirim isian') }}</button><span class="faint" style="font-size:12px">{{ __('Isian tersimpan otomatis. Setelah dikirim, dokumen disusun per bagian.') }}</span></div>
</form>
<script src="{{ asset('js/gho-pform.js') }}?v=1" defer></script>
