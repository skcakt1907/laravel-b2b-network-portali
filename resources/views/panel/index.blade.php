@extends('layouts.panel')

@section('title', 'Panel')
@section('subtitle', 'Hos geldiniz, '.$uye->name.'.')

@section('content')

    {{-- ----------------------------------------------------- hatirlatmalar --}}
    @if($profilEksik)
        <div class="alert-ui alert-warn mb-4">
            <i class="bi bi-person-exclamation"></i>
            <div class="flex-grow-1">
                <strong>Asansor cumleniz eksik.</strong>
                Ne yaptiginizi ve ne aradiginizi yazmadan uye dizini aramalarinda
                bulunamazsiniz.
            </div>
            <a class="btn btn-primary btn-sm" href="{{ route('profilim') }}">Doldur</a>
        </div>
    @endif

    @if($yanitBekleyen > 0)
        <div class="alert-ui alert-info mb-4">
            <i class="bi bi-calendar-check"></i>
            <div class="flex-grow-1">
                {{ $yanitBekleyen }} etkinlik icin katilim yanitiniz bekleniyor.
            </div>
            <a class="btn btn-outline btn-sm" href="{{ route('etkinlikler') }}">Etkinlikler</a>
        </div>
    @endif

    {{-- ----------------------------------------------------- sayilar --}}
    <div class="row g-3 mb-4">
        @unless($uye->isMisafir())
            <div class="col-6 col-lg-3">
                <div class="stat">
                    <div class="stat-label">Unitycoin bakiyeniz</div>
                    <div class="stat-value">
                        {{ number_format($uye->coinBalance(), 0, ',', '.') }} <small>UC</small>
                    </div>
                </div>
            </div>
        @endunless

        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Katildiginiz etkinlik</div>
                <div class="stat-value">{{ $katildigiEtkinlik }}</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Rolunuz</div>
                <div class="stat-value" style="font-size:1.1rem;padding-top:.35rem">
                    {{ ['admin' => 'Yonetici', 'uye' => 'Uye', 'misafir' => 'Misafir'][$uye->role] }}
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

    <div class="row g-4">

        {{-- ------------------------------------------------- yaklasan etkinlikler --}}
        <div class="col-lg-7">
            <div class="card-ui h-100">
                <div class="card-ui-head">
                    <h2>Yaklasan etkinlikler</h2>
                    <a class="btn btn-ghost btn-sm" href="{{ route('etkinlikler') }}">Tumu</a>
                </div>
                <div class="card-ui-body p-0">
                    @if($etkinlikler->isEmpty())
                        <div class="empty">
                            <i class="bi bi-calendar-x"></i>
                            Yaklasan etkinlik yok.
                        </div>
                    @else
                        <table class="table-ui">
                            <tbody>
                                @foreach($etkinlikler as $e)
                                    @php $yanit = $yanitlar[$e->id] ?? null; @endphp
                                    <tr>
                                        <td style="width:64px">
                                            <div style="text-align:center;background:var(--nav-100);
                                                        border-radius:8px;padding:.35rem">
                                                <div style="font-size:1.1rem;font-weight:700;line-height:1;color:var(--nav)">
                                                    {{ $e->starts_at->format('d') }}
                                                </div>
                                                <div style="font-size:.6875rem;color:var(--nav);text-transform:uppercase">
                                                    {{ $e->starts_at->translatedFormat('M') }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600">{{ $e->title }}</div>
                                            <div class="text-muted-2" style="font-size:.8125rem">
                                                {{ $e->starts_at->format('H:i') }}
                                                @if($e->type === 'online') &middot; Online @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($yanit === 'katiliyor')
                                                <span class="badge-ui badge-ok">Katiliyorsunuz</span>
                                            @elseif($yanit === 'katilmiyor')
                                                <span class="badge-ui badge-err">Katilmiyorsunuz</span>
                                            @else
                                                <span class="badge-ui badge-warn">Yanit verin</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-outline btn-sm"
                                               href="{{ route('etkinlikler.detay', $e) }}">Ac</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------- kisayollar --}}
        <div class="col-lg-5">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Kisayollar</h2></div>
                <div class="card-ui-body d-flex flex-column gap-2">

                    @can('viewAny', App\Models\User::class)
                        <a class="btn btn-outline w-100 text-start" href="{{ route('uyeler') }}">
                            <i class="bi bi-people"></i> Uye dizininde ara
                        </a>
                    @endcan

                    <a class="btn btn-outline w-100 text-start" href="{{ route('etkinlikler') }}">
                        <i class="bi bi-calendar-event"></i> Etkinlik takvimi
                    </a>

                    <a class="btn btn-outline w-100 text-start" href="{{ route('profilim') }}">
                        <i class="bi bi-person-badge"></i> Profilimi duzenle
                    </a>

                    @if(auth()->user()->isAdmin())
                        <hr style="border-color:var(--line)">
                        <a class="btn btn-primary w-100 text-start" href="{{ route('yonetim.index') }}">
                            <i class="bi bi-speedometer2"></i> Yonetim paneli
                        </a>
                    @endif

                </div>
            </div>
        </div>

    </div>

@endsection
