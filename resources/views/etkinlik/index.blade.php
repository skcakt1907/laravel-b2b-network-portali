@extends('layouts.panel')

@section('title', 'Etkinlikler')
@section('subtitle', $gecmis ? 'Gecmis etkinlikler' : 'Yaklasan toplanti, egitim ve gezileri')

@section('content')

    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('etkinlikler') }}"
           class="btn btn-sm {{ $gecmis ? 'btn-outline' : 'btn-primary' }}">Yaklasan</a>
        <a href="{{ route('etkinlikler', ['gecmis' => 1]) }}"
           class="btn btn-sm {{ $gecmis ? 'btn-primary' : 'btn-outline' }}">Gecmis</a>
    </div>

    @if($etkinlikler->isEmpty())
        <div class="card-ui">
            <div class="card-ui-body">
                <div class="empty">
                    <i class="bi bi-calendar-x"></i>
                    {{ $gecmis ? 'Gecmis etkinlik yok.' : 'Yaklasan etkinlik yok.' }}
                </div>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($etkinlikler as $e)
                @php $yanit = $yanitlar[$e->id] ?? null; @endphp
                <div class="col-md-6">
                    <div class="card-ui card-hover h-100">
                        <div class="card-ui-body d-flex flex-column h-100">

                            <div class="d-flex gap-3">
                                {{-- Tarih blogu --}}
                                <div style="flex:0 0 auto;text-align:center;min-width:56px;
                                            background:var(--nav-100);border-radius:8px;padding:.5rem">
                                    <div style="font-size:1.4rem;font-weight:700;line-height:1;color:var(--nav)">
                                        {{ $e->starts_at->format('d') }}
                                    </div>
                                    <div style="font-size:.75rem;color:var(--nav);text-transform:uppercase">
                                        {{ $e->starts_at->translatedFormat('M') }}
                                    </div>
                                </div>

                                <div style="min-width:0">
                                    <a href="{{ route('etkinlikler.detay', $e) }}"
                                       style="font-weight:600;color:var(--ink);text-decoration:none">
                                        {{ $e->title }}
                                    </a>
                                    <div class="text-muted-2" style="font-size:.875rem">
                                        <i class="bi bi-clock"></i> {{ $e->starts_at->format('H:i') }}
                                        @if($e->type === 'online')
                                            &middot; <i class="bi bi-camera-video"></i> Online
                                        @elseif($e->location)
                                            &middot; <i class="bi bi-geo-alt"></i> {{ $e->location }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2 d-flex gap-1 flex-wrap">
                                <span class="badge-ui badge-nav">
                                    {{ ['toplanti' => 'Toplanti', 'egitim' => 'Egitim', 'gezi' => 'Gezi', 'diger' => 'Diger'][$e->category] }}
                                </span>
                                @if($e->visibility === 'uye_misafir')
                                    <span class="badge-ui badge-nav"><i class="bi bi-person-plus"></i> Misafire acik</span>
                                @endif
                                @if($yanit === 'katiliyor')
                                    <span class="badge-ui badge-ok"><i class="bi bi-check-circle"></i> Katiliyorsunuz</span>
                                @elseif($yanit === 'katilmiyor')
                                    <span class="badge-ui badge-err">Katilmiyorsunuz</span>
                                @elseif(! $gecmis)
                                    <span class="badge-ui badge-warn">Yanit bekleniyor</span>
                                @endif
                            </div>

                            @if($e->description)
                                <p class="text-muted-2 mt-3 mb-0" style="font-size:.875rem">
                                    {{ Str::limit($e->description, 120) }}
                                </p>
                            @endif

                            <div class="mt-auto pt-3 d-flex align-items-center justify-content-between">
                                <span class="text-muted-2" style="font-size:.8125rem">
                                    <i class="bi bi-people"></i>
                                    {{ $e->katilan_sayisi }} katilimci
                                    @if($e->capacity) / {{ $e->capacity }} @endif
                                </span>
                                <a class="btn btn-outline btn-sm" href="{{ route('etkinlikler.detay', $e) }}">
                                    Detay
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($etkinlikler->hasPages())
            <div class="mt-4">{{ $etkinlikler->links() }}</div>
        @endif
    @endif

@endsection
