@extends('layouts.panel')

@section('title', 'Yonetim paneli')
@section('subtitle', now()->translatedFormat('d F Y, l'))

@section('content')

    {{-- ----------------------------------------------------- dikkat gerektirenler --}}
    @if($sayilar['bekleyenBasvuru'] > 0)
        <div class="alert-ui alert-warn mb-4">
            <i class="bi bi-inbox"></i>
            <div class="flex-grow-1">
                <strong>{{ $sayilar['bekleyenBasvuru'] }} uyelik basvurusu</strong> incelenmeyi bekliyor.
            </div>
            <a class="btn btn-primary btn-sm" href="{{ route('yonetim.basvurular.index') }}">Incele</a>
        </div>
    @endif

    {{-- ----------------------------------------------------- sayilar --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Aktif uye</div>
                <div class="stat-value">{{ $sayilar['aktifUye'] }}</div>
                @if($sayilar['misafir'] > 0)
                    <div class="text-muted-2" style="font-size:.8125rem">
                        + {{ $sayilar['misafir'] }} misafir
                    </div>
                @endif
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Bekleyen basvuru</div>
                <div class="stat-value" @style(['color:var(--warn)' => $sayilar['bekleyenBasvuru'] > 0])>
                    {{ $sayilar['bekleyenBasvuru'] }}
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Yaklasan etkinlik</div>
                <div class="stat-value">{{ $sayilar['yaklasanEtkinlik'] }}</div>
                @if($taslakEtkinlikler > 0)
                    <div class="text-muted-2" style="font-size:.8125rem">
                        + {{ $taslakEtkinlikler }} taslak
                    </div>
                @endif
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Dolasimdaki Unitycoin</div>
                <div class="stat-value">
                    {{ number_format($sayilar['dolasimdakiCoin'], 0, ',', '.') }} <small>UC</small>
                </div>
                <div class="text-muted-2" style="font-size:.8125rem">
                    toplam {{ number_format($sayilar['dagitilanCoin'], 0, ',', '.') }} dagitildi
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ----------------------------------------------- bekleyen basvurular --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head">
                    <h2>Bekleyen basvurular</h2>
                    <a class="btn btn-ghost btn-sm" href="{{ route('yonetim.basvurular.index') }}">Tumu</a>
                </div>
                <div class="card-ui-body p-0">
                    @if($bekleyenBasvurular->isEmpty())
                        <div class="empty">
                            <i class="bi bi-check2-circle"></i>
                            Bekleyen basvuru yok.
                        </div>
                    @else
                        <table class="table-ui">
                            <tbody>
                                @foreach($bekleyenBasvurular as $b)
                                    <tr>
                                        <td>
                                            <div style="font-weight:600">{{ $b->name }}</div>
                                            <div class="text-muted-2" style="font-size:.8125rem">
                                                {{ $b->company_name ?: $b->email }}
                                                @if($b->invitation?->inviter)
                                                    &middot; davet: {{ $b->invitation->inviter->name }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-muted-2" style="font-size:.8125rem;white-space:nowrap">
                                            {{ $b->created_at->diffForHumans() }}
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-outline btn-sm"
                                               href="{{ route('yonetim.basvurular.show', $b) }}">Ac</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- ----------------------------------------------- yaklasan etkinlikler --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head">
                    <h2>Yaklasan etkinlikler</h2>
                    <a class="btn btn-ghost btn-sm" href="{{ route('yonetim.etkinlikler.index') }}">Tumu</a>
                </div>
                <div class="card-ui-body p-0">
                    @if($yaklasanEtkinlikler->isEmpty())
                        <div class="empty">
                            <i class="bi bi-calendar-x"></i>
                            Yaklasan etkinlik yok.
                            <div class="mt-2">
                                <a class="btn btn-primary btn-sm"
                                   href="{{ route('yonetim.etkinlikler.olustur') }}">Etkinlik olustur</a>
                            </div>
                        </div>
                    @else
                        <table class="table-ui">
                            <tbody>
                                @foreach($yaklasanEtkinlikler as $e)
                                    <tr>
                                        <td>
                                            <div style="font-weight:600">{{ $e->title }}</div>
                                            <div class="text-muted-2" style="font-size:.8125rem">
                                                {{ $e->starts_at->translatedFormat('d F, H:i') }}
                                                &middot; {{ $e->katilan_sayisi }} katiliyor
                                                @if($e->capacity) / {{ $e->capacity }} @endif
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-outline btn-sm"
                                               href="{{ route('yonetim.etkinlikler.duzenle', $e) }}">Yonet</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- ----------------------------------------------- ozel gunler --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head">
                    <h2>{{ now()->translatedFormat('F') }} ayindaki ozel gunler</h2>
                </div>
                <div class="card-ui-body">
                    @if(empty($ozelGunler))
                        <div class="empty">
                            <i class="bi bi-gift"></i>
                            Bu ay kutlanacak ozel gun yok.
                        </div>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($ozelGunler as $g)
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-{{ $g['tur'] === 'dogum' ? 'balloon' : 'building' }}"
                                       style="color:var(--gold-700)"></i>
                                    <div class="flex-grow-1">
                                        <strong>{{ $g['ad'] }}</strong>
                                        <span class="text-muted-2" style="font-size:.875rem">
                                            @if($g['tur'] === 'dogum')
                                                dogum gunu
                                            @else
                                                kurulusunun {{ $g['yil'] }}. yili
                                            @endif
                                        </span>
                                    </div>
                                    <span class="badge-ui badge-gold">{{ $g['gun'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="alert-ui alert-info mt-3">
                            <i class="bi bi-info-circle"></i>
                            <div style="font-size:.8125rem">
                                Otomatik kutlama mesaji henuz yok; su an elle kutlamaniz gerekiyor.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ----------------------------------------------- topluluk sagligi --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Topluluk sagligi</h2></div>
                <div class="card-ui-body">

                    @if($profiliEksikSayisi > 0)
                        <div class="alert-ui alert-warn mb-3">
                            <i class="bi bi-person-exclamation"></i>
                            <div>
                                <strong>{{ $profiliEksikSayisi }} uye</strong> asansor cumlesini
                                doldurmamis. Bu uyeler dizin aramalarinda bulunamiyor.
                            </div>
                        </div>
                    @else
                        <div class="alert-ui alert-ok mb-3">
                            <i class="bi bi-check-circle"></i>
                            <div>Tum uyeler asansor cumlesini doldurmus.</div>
                        </div>
                    @endif

                    @if($sayilar['dondurulmus'] > 0)
                        <div class="alert-ui alert-info mb-3">
                            <i class="bi bi-pause-circle"></i>
                            <div>
                                {{ $sayilar['dondurulmus'] }} uyelik dondurulmus durumda.
                                <a href="{{ route('yonetim.uyeler.index', ['durum' => 'donduruldu']) }}">Goruntule</a>
                            </div>
                        </div>
                    @endif

                    <div class="text-muted-2 mb-2" style="font-size:.8125rem;text-transform:uppercase;letter-spacing:.05em">
                        Son katilanlar
                    </div>

                    @if($sonUyeler->isEmpty())
                        <div class="text-muted-2">Henuz uye yok.</div>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($sonUyeler as $u)
                                <div class="d-flex align-items-center gap-2">
                                    @include('partials.avatar', ['uye' => $u, 'boyut' => 32])
                                    <div class="flex-grow-1" style="min-width:0">
                                        <div style="font-size:.9375rem;font-weight:550">{{ $u->name }}</div>
                                        <div class="text-muted-2" style="font-size:.8125rem">
                                            {{ $u->company?->name ?: '—' }}
                                        </div>
                                    </div>
                                    <a class="btn btn-ghost btn-sm"
                                       href="{{ route('yonetim.uyeler.show', $u) }}">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>

@endsection
