@php
    // WhatsApp bagi icin numaradaki bosluk/parantez temizlenir ve ulke kodu eklenir
    $wa = preg_replace('/\D+/', '', (string) ($uye->profile?->whatsapp ?? $uye->phone));

    if ($wa !== '' && str_starts_with($wa, '0')) {
        $wa = '90'.substr($wa, 1);
    } elseif ($wa !== '' && ! str_starts_with($wa, '90') && strlen($wa) === 10) {
        $wa = '90'.$wa;
    }

    $genis = $genis ?? false;
@endphp

<div class="d-flex gap-2 flex-wrap">
    @if($wa !== '')
        <a class="btn btn-outline btn-sm {{ $genis ? 'flex-grow-1' : '' }}"
           href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp"></i> WhatsApp
        </a>
    @endif

    <a class="btn btn-outline btn-sm {{ $genis ? 'flex-grow-1' : '' }}"
       href="mailto:{{ $uye->email }}">
        <i class="bi bi-envelope"></i> E-posta
    </a>

    @if($uye->phone)
        <a class="btn btn-outline btn-sm {{ $genis ? 'flex-grow-1' : '' }}"
           href="tel:{{ preg_replace('/\s+/', '', $uye->phone) }}">
            <i class="bi bi-telephone"></i> Ara
        </a>
    @endif
</div>
