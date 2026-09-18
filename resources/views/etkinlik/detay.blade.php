@extends('layouts.panel')

@section('title', $etkinlik->title)
@section('subtitle', $etkinlik->starts_at->translatedFormat('d F Y, l · H:i'))

@section('content')

    <div class="mb-3">
        <a class="btn btn-ghost btn-sm" href="{{ route('etkinlikler') }}">
            <i class="bi bi-arrow-left"></i> Etkinlikler
        </a>
    </div>

    @php
        $gecti = $etkinlik->starts_at->isPast();
        $dolu = $etkinlik->kontenjanDoluMu();
        $katiliyor = $katilim?->rsvp === 'katiliyor';
    @endphp

    <div class="row g-4">
        <div class="col-lg-7">

            <div class="card-ui mb-4">
                <div class="card-ui-head">
                    <h2>Etkinlik bilgileri</h2>
                    <span class="badge-ui badge-nav">
                        {{ ['toplanti' => 'Toplanti', 'egitim' => 'Egitim', 'gezi' => 'Gezi', 'diger' => 'Diger'][$etkinlik->category] }}
                    </span>
                </div>
                <div class="card-ui-body">

                    @if($etkinlik->description)
                        <p style="white-space:pre-line">{{ $etkinlik->description }}</p>
                        <hr style="border-color:var(--line)">
                    @endif

                    <table class="table-ui">
                        <tbody>
                            <tr>
                                <th style="width:34%">Tarih</th>
                                <td>{{ $etkinlik->starts_at->translatedFormat('d F Y, H:i') }}</td>
                            </tr>
                            @if($etkinlik->ends_at)
                                <tr><th>Bitis</th><td>{{ $etkinlik->ends_at->format('H:i') }}</td></tr>
                            @endif
                            <tr>
                                <th>Yer</th>
                                <td>
                                    @if($etkinlik->type === 'online')
                                        <i class="bi bi-camera-video"></i> Online toplanti
                                    @else
                                        {{ $etkinlik->location ?: '—' }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Katilimci</th>
                                <td>
                                    {{ $katilanSayisi }}
                                    @if($etkinlik->capacity)
                                        / {{ $etkinlik->capacity }}
                                        @if($dolu)<span class="badge-ui badge-err ms-1">Dolu</span>@endif
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Toplanti bagi yalnizca KATILIYORUM diyene gosterilir --}}
                    @if($etkinlik->type === 'online' && $etkinlik->online_url)
                        @if($katiliyor)
                            <a class="btn btn-gold w-100 mt-3" href="{{ $etkinlik->online_url }}"
                               target="_blank" rel="noopener">
                                <i class="bi bi-camera-video"></i> Toplantiya katil
                            </a>
                        @else
                            <div class="alert-ui alert-info mt-3">
                                <i class="bi bi-lock"></i>
                                <div>Toplanti bagi, katilacaginizi bildirdikten sonra gorunur.</div>
                            </div>
                        @endif
                    @endif

                </div>
            </div>

            {{-- ------------------------------------------- katilimci listesi --}}
            @if($katilimcilar->isNotEmpty())
                <div class="card-ui">
                    <div class="card-ui-head">
                        <h2>Katilimcilar</h2>
                        @if($etkinlik->has_presentations)
                            <span class="badge-ui badge-gold">
                                <i class="bi bi-mic"></i> 3 dakika sunum var
                            </span>
                        @endif
                    </div>
                    <div class="card-ui-body p-0">
                        <table class="table-ui">
                            <thead>
                                <tr>
                                    @if($etkinlik->has_presentations)<th style="width:70px">Sira</th>@endif
                                    <th>Uye</th>
                                    <th>Firma</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($katilimcilar as $k)
                                    <tr @class(['fw-semibold' => $k->user_id === auth()->id()])>
                                        @if($etkinlik->has_presentations)
                                            <td>
                                                @if($k->presentation_order)
                                                    <span class="badge-ui badge-nav">{{ $k->presentation_order }}</span>
                                                @else
                                                    <span class="text-muted-2">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            {{ $k->user->name }}
                                            @if($k->user_id === auth()->id())
                                                <span class="badge-ui badge-ok ms-1">Siz</span>
                                            @endif
                                        </td>
                                        <td class="text-muted-2">{{ $k->user->company?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>

        {{-- ------------------------------------------- katilim onayi --}}
        <div class="col-lg-5">
            <div class="card-ui">
                <div class="card-ui-head"><h2>Katilim durumunuz</h2></div>
                <div class="card-ui-body">

                    @if($gecti)
                        <div class="alert-ui alert-info">
                            <i class="bi bi-clock-history"></i>
                            <div>Bu etkinlik gecti; katilim onayi degistirilemez.</div>
                        </div>
                        @if($katilim)
                            <p class="mt-3 mb-0">
                                Yanitiniz:
                                <strong>{{ $katiliyor ? 'Katiliyorum' : 'Katilamiyorum' }}</strong>
                                @if($katilim->attended)
                                    <span class="badge-ui badge-ok ms-1">Katildiniz</span>
                                @endif
                            </p>
                        @endif
                    @else
                        @if($katilim)
                            <div class="alert-ui {{ $katiliyor ? 'alert-ok' : 'alert-warn' }} mb-3">
                                <i class="bi bi-{{ $katiliyor ? 'check-circle' : 'dash-circle' }}"></i>
                                <div>
                                    {{ $katiliyor ? 'Katilacaginizi bildirdiniz.' : 'Katilamayacaginizi bildirdiniz.' }}
                                    <div style="font-size:.8125rem;opacity:.85">
                                        Yanitinizi degistirebilirsiniz.
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($etkinlik->has_presentations && $katilim?->presentation_order)
                            <div class="alert-ui alert-info mb-3">
                                <i class="bi bi-mic"></i>
                                <div>
                                    3 dakikalik sunum sirasi:
                                    <strong>{{ $katilim->presentation_order }}</strong>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('etkinlikler.katilim', $etkinlik) }}">
                            @csrf
                            <input type="hidden" name="rsvp" value="katiliyor">
                            <button class="btn btn-primary w-100 mb-2" type="submit"
                                    @disabled($dolu && ! $katiliyor)>
                                <i class="bi bi-check-lg"></i>
                                {{ $katiliyor ? 'Katiliyorum (secili)' : 'Katiliyorum' }}
                            </button>
                        </form>

                        @if($dolu && ! $katiliyor)
                            <div class="form-hint mb-2">Kontenjan dolu.</div>
                        @endif

                        <form method="POST" action="{{ route('etkinlikler.katilim', $etkinlik) }}">
                            @csrf
                            <input type="hidden" name="rsvp" value="katilmiyor">
                            <button class="btn btn-outline w-100" type="submit">
                                Katilamiyorum
                            </button>
                        </form>
                    @endif

                </div>
            </div>
        </div>
    </div>

@endsection
