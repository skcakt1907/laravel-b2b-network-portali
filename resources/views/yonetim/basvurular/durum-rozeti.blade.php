@php
    $stil = ['beklemede' => 'warn', 'onaylandi' => 'ok', 'reddedildi' => 'err'][$durum] ?? 'nav';
    $etiket = ['beklemede' => 'Bekliyor', 'onaylandi' => 'Onaylandi', 'reddedildi' => 'Reddedildi'][$durum] ?? $durum;
    $ikon = ['warn' => 'hourglass-split', 'ok' => 'check-circle', 'err' => 'x-circle'][$stil] ?? 'circle';
@endphp

<span class="badge-ui badge-{{ $stil }}"><i class="bi bi-{{ $ikon }}"></i> {{ $etiket }}</span>
