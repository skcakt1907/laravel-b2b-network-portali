@php
    // Tam eslesme ya da alt sayfa (ornek: /etkinlikler/aylik-toplanti) aktif sayilir
    $aktif = request()->url() === $href || str_starts_with(request()->url(), $href.'/');
@endphp

<a href="{{ $href }}" class="sidebar-link {{ $aktif ? 'active' : '' }}">
    <i class="bi bi-{{ $icon }}"></i>
    <span>{{ $label }}</span>
</a>
