@php
    $boyut = $boyut ?? 48;
    // Fotograf yoksa bas harfler gosterilir
    $harfler = collect(preg_split('/\s+/', trim($uye->name)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
@endphp

@if($uye->avatar_path)
    <img src="{{ asset($uye->avatar_path) }}" alt="{{ $uye->name }}"
         style="width:{{ $boyut }}px;height:{{ $boyut }}px;border-radius:50%;
                object-fit:cover;flex:0 0 auto;border:1px solid var(--line)">
@else
    <div style="width:{{ $boyut }}px;height:{{ $boyut }}px;border-radius:50%;
                background:var(--nav-100);color:var(--nav);font-weight:700;
                font-size:{{ round($boyut / 2.6) }}px;flex:0 0 auto;
                display:flex;align-items:center;justify-content:center"
         aria-label="{{ $uye->name }}">{{ $harfler ?: '?' }}</div>
@endif
