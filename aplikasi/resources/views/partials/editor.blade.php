{{-- Editor teks lengkap. $id, $name, $html, $upload (bool), $save (url autosave atau null) --}}
@php $upload = $upload ?? false; $save = $save ?? null; @endphp
<div class="ed" id="{{ $id }}" data-name="{{ $name }}" @if ($save) data-save="{{ $save }}" @endif>
  @if ($upload)
    <div class="tabs edmode"><button class="on" type="button" data-mode="ketik">@ic('pen', 'width:14px;height:14px;vertical-align:-2px') {{ __('Ketik langsung') }}</button><button type="button" data-mode="unggah">@ic('up', 'width:14px;height:14px;vertical-align:-2px') {{ __('Unggah dokumen') }}</button></div>
  @endif
  <div class="ed-ketik">
    <div class="ed-bar" role="toolbar" aria-label="{{ __('Format teks') }}">
      <div class="gp"><button type="button" data-cmd="undo" title="{{ __('Urungkan') }}">↶</button><button type="button" data-cmd="redo" title="{{ __('Ulangi') }}">↷</button></div>
      <div class="gp"><select data-cmd="formatBlock" aria-label="{{ __('Gaya paragraf') }}"><option value="">{{ __('Gaya') }}</option><option value="p">{{ __('Normal') }}</option><option value="h2">{{ __('Judul 1') }}</option><option value="h3">{{ __('Judul 2') }}</option><option value="blockquote">{{ __('Kutipan') }}</option></select>
        <select data-cmd="fontSize" aria-label="{{ __('Ukuran huruf') }}"><option value="">{{ __('Ukuran') }}</option><option value="2">{{ __('Kecil') }}</option><option value="3">{{ __('Normal') }}</option><option value="4">{{ __('Besar') }}</option><option value="5">{{ __('Sangat besar') }}</option></select></div>
      <div class="gp"><button type="button" data-cmd="bold" title="{{ __('Tebal') }}"><b>B</b></button><button type="button" data-cmd="italic" title="{{ __('Miring') }}"><i>I</i></button><button type="button" data-cmd="underline" title="{{ __('Garis bawah') }}"><u>U</u></button><button type="button" data-cmd="strikeThrough" title="{{ __('Coret') }}"><s>S</s></button><button type="button" data-cmd="subscript" title="{{ __('Subskrip') }}">x₂</button><button type="button" data-cmd="superscript" title="{{ __('Superskrip') }}">x²</button></div>
      <div class="gp"><input type="color" data-cmd="foreColor" value="#c2410c" title="{{ __('Warna huruf') }}" aria-label="{{ __('Warna huruf') }}"><input type="color" data-cmd="hiliteColor" value="#fff176" title="{{ __('Stabilo') }}" aria-label="{{ __('Stabilo') }}"></div>
      <div class="gp"><button type="button" data-cmd="justifyLeft" title="{{ __('Rata kiri') }}">⯇</button><button type="button" data-cmd="justifyCenter" title="{{ __('Tengah') }}">≡</button><button type="button" data-cmd="justifyRight" title="{{ __('Rata kanan') }}">⯈</button><button type="button" data-cmd="justifyFull" title="{{ __('Rata kiri kanan') }}">☰</button></div>
      <div class="gp"><button type="button" data-cmd="insertUnorderedList" title="{{ __('Daftar berpoin') }}">•≡</button><button type="button" data-cmd="insertOrderedList" title="{{ __('Daftar bernomor') }}">1.≡</button><button type="button" data-cmd="outdent" title="{{ __('Kurangi indentasi') }}">⇤</button><button type="button" data-cmd="indent" title="{{ __('Tambah indentasi') }}">⇥</button></div>
      <div class="gp"><button type="button" data-act="table" title="{{ __('Tabel') }}">▦</button><button type="button" data-act="link" title="{{ __('Tautan') }}">🔗</button><button type="button" data-cmd="insertHorizontalRule" title="{{ __('Garis') }}">―</button><button type="button" data-act="sym" title="{{ __('Simbol') }}">Ω</button><button type="button" data-cmd="removeFormat" title="{{ __('Hapus format') }}">T̸</button></div>
      <div class="gp"><button type="button" class="eqb" data-act="eq" title="{{ __('Sisipkan persamaan') }}">∑ {{ __('Persamaan') }}</button></div>
    </div>
    <div class="ed-pop" hidden></div>
    <div class="ed-area" contenteditable="true" spellcheck="true">{!! $html !!}</div>
    <div class="ed-foot"><span class="wc">0 {{ __('kata') }}</span><span>{{ $save ? __('Tersimpan otomatis') : '' }} · {{ __('klik persamaan untuk mengedit') }}</span></div>
  </div>
  @if ($upload)
    <div class="ed-unggah" hidden><label class="drop">@ic('up', 'width:28px;height:28px')<b>{{ __('Pilih file PDF, Word, PowerPoint, Excel, atau gambar') }}</b><span class="faint" style="font-size:12.5px">{{ __('Maksimal 10 MB.') }}</span><input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png"></label><div class="fileinfo" style="margin:0 12px 12px"></div></div>
  @endif
  <input type="hidden" name="{{ $name }}" value="{{ $html }}">
</div>
