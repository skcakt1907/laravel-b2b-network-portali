@php
    $stil = [
        'aktif' => 'ok',
        'beklemede' => 'warn',
        'donduruldu' => 'err',
        'pasif' => 'nav',
    ][$durum] ?? 'nav';

    $etiket = [
        'aktif' => 'Aktif',
        'beklemede' => 'Onay bekliyor',
        'donduruldu' => 'Dondurulmus',
        'pasif' => 'Pasif',
    ][$durum] ?? $durum;

    $ikon = [
        'ok' => 'check-circle',
        'warn' => 'hourglass-split',
        'err' => 'pause-circle',
        'nav' => 'slash-circle',
    ][$stil];
@endphp

<span class="badge-ui badge-{{ $stil }}"><i class="bi bi-{{ $ikon }}"></i> {{ $etiket }}</span>
