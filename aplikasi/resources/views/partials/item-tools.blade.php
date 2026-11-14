{{-- Alat untuk satu item di struktur: urutan wajib, jadwal, pindah, hapus. $level, $item, $label --}}
@php $fmt = fn ($d) => $d ? $d->format('Y-m-d\TH:i') : ''; $w = \App\Services\Progress::inWindow($item); @endphp
@if ($item->opens_at || $item->closes_at)
  <span class="chip {{ $w === 'tutup' ? 'bad' : ($w === 'belum' ? 'warn' : 'good') }}" title="{{ __('Jadwal') }}">@ic('cal', 'width:12px;height:12px'){{ $item->opens_at?->translatedFormat('d M H:i') ?? '…' }} – {{ $item->closes_at?->translatedFormat('d M H:i') ?? '…' }}</span>
@endif
<label class="sw" title="{{ __('Jika aktif, item sebelumnya wajib selesai dulu') }}" onclick="event.stopPropagation()">
  <input type="checkbox" {{ $item->sequential ? 'checked' : '' }} onchange="ghoSeq(this, '{{ route('fas.item.seq', [$level, $item->id]) }}')"> {{ __('Berurutan') }}
</label>
<details class="inline" onclick="event.stopPropagation()">
  <summary class="btn sm ghost" style="display:inline-flex">@ic('cal', 'width:14px;height:14px'){{ __('Jadwal') }}</summary>
  <form method="post" action="{{ route('fas.item.update', [$level, $item->id]) }}" class="card stack" style="position:absolute;z-index:5;max-width:320px;margin-top:6px;box-shadow:var(--shadow)">@csrf @method('put')
    <div class="field"><label>{{ __('Nama') }}</label><input type="text" name="name" value="{{ $label }}" required></div>
    <div class="field"><label>{{ __('Dibuka') }}</label><input type="datetime-local" name="opens_at" value="{{ $fmt($item->opens_at) }}"></div>
    <div class="field"><label>{{ __('Ditutup') }}</label><input type="datetime-local" name="closes_at" value="{{ $fmt($item->closes_at) }}"></div>
    <button class="btn sm primary">{{ __('Simpan') }}</button>
  </form>
</details>
<details class="inline" onclick="event.stopPropagation()">
  <summary class="btn sm ghost" style="display:inline-flex">@ic('send', 'width:14px;height:14px'){{ __('Bagikan') }}</summary>
  <form method="post" action="{{ route('fas.share.store', [$level, $item->id]) }}" class="card stack" style="position:absolute;z-index:5;max-width:320px;margin-top:6px;box-shadow:var(--shadow)">@csrf
    <b>{{ __('Tautan untuk peserta') }}</b><span class="faint" style="font-size:12px">{{ __('Tautan hanya bisa dibuka di antara waktu mulai dan akhir.') }}</span>
    <div class="field"><label>{{ __('Waktu mulai') }}</label><input type="datetime-local" name="opens_at" value="{{ $fmt($item->opens_at) }}"></div>
    <div class="field"><label>{{ __('Waktu akhir') }}</label><input type="datetime-local" name="closes_at" value="{{ $fmt($item->closes_at) }}"></div>
    <button class="btn sm primary">{{ __('Buat tautan') }}</button>
  </form>
</details>
@if ($level !== 'pelatihan')
  <form method="post" action="{{ route('fas.item.move', [$level, $item->id, 'naik']) }}" class="inline" onclick="event.stopPropagation()">@csrf<button class="btn sm ghost" title="{{ __('Naik') }}" aria-label="{{ __('Naik') }}">↑</button></form>
  <form method="post" action="{{ route('fas.item.move', [$level, $item->id, 'turun']) }}" class="inline" onclick="event.stopPropagation()">@csrf<button class="btn sm ghost" title="{{ __('Turun') }}" aria-label="{{ __('Turun') }}">↓</button></form>
  <form method="post" action="{{ route('fas.item.delete', [$level, $item->id]) }}" class="inline" onclick="event.stopPropagation()" onsubmit="return confirm('{{ __('Hapus beserta isinya?') }}')">@csrf @method('delete')<button class="btn sm ghost" title="{{ __('Hapus') }}" aria-label="{{ __('Hapus') }}">@ic('trash', 'width:14px;height:14px')</button></form>
@endif
