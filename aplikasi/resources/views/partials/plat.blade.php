@php $pm = \App\Models\SocialPost::PLATFORMS[$p] ?? ['?', '#777', '?']; @endphp
<span class="pl" title="{{ $pm[0] }}" style="background:{{ $pm[1] }};width:{{ $size ?? 26 }}px;height:{{ $size ?? 26 }}px;border-radius:7px;display:inline-grid;place-items:center;color:#fff;font-weight:800;font-size:11px;flex:none">{{ $pm[2] }}</span>
