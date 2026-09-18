@extends('layouts.panel')

@section('title', 'Panel')
@section('subtitle', 'Hos geldiniz, '.auth()->user()->name.'.')

@section('content')

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Unitycoin bakiyeniz</div>
                <div class="stat-value">{{ number_format(auth()->user()->coinBalance(), 0, ',', '.') }} <small>UC</small></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Katildiginiz etkinlik</div>
                <div class="stat-value">{{ auth()->user()->attendances()->where('attended', true)->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Rolunuz</div>
                <div class="stat-value" style="font-size:1.1rem;padding-top:.35rem">
                    {{ ['admin' => 'Yonetici', 'uye' => 'Uye', 'misafir' => 'Misafir'][auth()->user()->role] }}
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Uyelik durumu</div>
                <div class="stat-value" style="font-size:1.1rem;padding-top:.35rem">
                    <span class="badge-ui badge-ok">Aktif</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card-ui">
        <div class="card-ui-head"><h2>Faz 1 - iskelet asamasi</h2></div>
        <div class="card-ui-body">
            <p class="mb-2">
                Kimlik dogrulama calisiyor. Sonraki fazlarda bu panele su bolumler eklenecek:
            </p>
            <ul class="text-muted-2 mb-0" style="font-size:.9375rem">
                <li>Yaklasan etkinlikler ve katilim onayi</li>
                <li>Uye dizini ve hizli iletisim</li>
                <li>Unitycoin cuzdani ve hareket dokumu</li>
                <li>Ayin Is Insani vitrini</li>
            </ul>
        </div>
    </div>

@endsection
