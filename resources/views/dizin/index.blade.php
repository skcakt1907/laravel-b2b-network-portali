@extends('layouts.panel')

@section('title', 'Uye dizini')
@section('subtitle', $uyeler->total().' uye listeleniyor.')

@section('content')

    {{-- ----------------------------------------------------- filtreler --}}
    <div class="card-ui mb-4">
        <div class="card-ui-body">
            <form method="GET" action="{{ route('uyeler') }}">
                <div class="row g-2">
                    <div class="col-lg-4">
                        <input class="form-control" type="search" name="q" value="{{ $arama }}"
                               placeholder="Isim, firma veya hizmet ara...">
                    </div>

                    <div class="col-6 col-lg-2">
                        <select class="form-select" name="sektor">
                            <option value="">Tum sektorler</option>
                            @foreach($sektorler as $s)
                                <option value="{{ $s }}" @selected($sektor === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <select class="form-select" name="sehir">
                            <option value="">Tum sehirler</option>
                            @foreach($sehirler as $s)
                                <option value="{{ $s }}" @selected($sehir === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <select class="form-select" name="marka">
                            <option value="">Tum markalar</option>
                            @foreach($markalar as $deger => $etiket)
                                <option value="{{ $deger }}" @selected($marka === $deger)>{{ $etiket }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">
                            <i class="bi bi-search"></i> Ara
                        </button>
                        @if($filtreVar)
                            <a class="btn btn-outline" href="{{ route('uyeler') }}" title="Filtreleri temizle">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ----------------------------------------------------- sonuclar --}}
    @if($uyeler->isEmpty())
        <div class="card-ui">
            <div class="card-ui-body">
                <div class="empty">
                    <i class="bi bi-search"></i>
                    @if($filtreVar)
                        Bu aramaya uyan uye bulunamadi.
                        <div class="mt-2">
                            <a class="btn btn-outline btn-sm" href="{{ route('uyeler') }}">Filtreleri temizle</a>
                        </div>
                    @else
                        Dizinde henuz gorunur uye yok.
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($uyeler as $uye)
                <div class="col-md-6 col-xl-4">
                    <div class="card-ui card-hover h-100">
                        <div class="card-ui-body d-flex flex-column h-100">

                            <div class="d-flex gap-3">
                                @include('partials.avatar', ['uye' => $uye, 'boyut' => 52])
                                <div style="min-width:0">
                                    <a href="{{ route('uyeler.show', $uye) }}"
                                       style="font-weight:600;color:var(--ink);text-decoration:none">
                                        {{ $uye->name }}
                                    </a>
                                    <div class="text-muted-2" style="font-size:.875rem">
                                        {{ collect([$uye->title, $uye->company?->name])->filter()->implode(' · ') ?: '—' }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2 d-flex gap-1 flex-wrap">
                                @if($uye->profile?->brandLabel())
                                    <span class="badge-ui badge-gold">{{ $uye->profile->brandLabel() }}</span>
                                @endif
                                @if($uye->company?->sector)
                                    <span class="badge-ui badge-nav">{{ $uye->company->sector }}</span>
                                @endif
                                @if($uye->company?->city)
                                    <span class="badge-ui badge-nav">
                                        <i class="bi bi-geo-alt"></i> {{ $uye->company->city }}
                                    </span>
                                @endif
                            </div>

                            @if($uye->profile?->services_pitch)
                                <p class="text-muted-2 mt-3 mb-0" style="font-size:.875rem">
                                    {{ Str::limit($uye->profile->services_pitch, 110) }}
                                </p>
                            @endif

                            <div class="mt-auto pt-3">
                                @include('partials.hizli-iletisim', ['uye' => $uye])
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($uyeler->hasPages())
            <div class="mt-4">{{ $uyeler->links() }}</div>
        @endif
    @endif

@endsection
