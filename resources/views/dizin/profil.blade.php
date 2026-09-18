@extends('layouts.panel')

@section('title', $uye->name)
@section('subtitle', collect([$uye->title, $uye->company?->name])->filter()->implode(' · '))

@section('content')

    <div class="mb-3">
        <a class="btn btn-ghost btn-sm" href="{{ route('uyeler') }}">
            <i class="bi bi-arrow-left"></i> Uye dizini
        </a>
    </div>

    <div class="row g-4">

        {{-- ------------------------------------------------ kisi karti --}}
        <div class="col-lg-4">
            <div class="card-ui">
                <div class="card-ui-body text-center">
                    <div class="d-flex justify-content-center mb-3">
                        @include('partials.avatar', ['uye' => $uye, 'boyut' => 96])
                    </div>

                    <h2 style="font-size:1.15rem;margin-bottom:.15rem">{{ $uye->name }}</h2>
                    <div class="text-muted-2" style="font-size:.9375rem">{{ $uye->title ?: '—' }}</div>

                    @if($uye->profile?->brandLabel())
                        <div class="mt-3">
                            <span class="badge-ui badge-gold">
                                <i class="bi bi-patch-check"></i> {{ $uye->profile->brandLabel() }}
                            </span>
                        </div>
                    @endif

                    <hr style="border-color:var(--line)">

                    @include('partials.hizli-iletisim', ['uye' => $uye, 'genis' => true])

                    @php
                        $sosyal = collect([
                            'linkedin' => $uye->profile?->linkedin,
                            'globe' => $uye->profile?->website,
                            'instagram' => $uye->profile?->instagram
                                ? 'https://instagram.com/'.$uye->profile->instagram
                                : null,
                        ])->filter();
                    @endphp

                    @if($sosyal->isNotEmpty())
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            @foreach($sosyal as $ikon => $bag)
                                <a class="btn btn-ghost btn-sm" href="{{ $bag }}"
                                   target="_blank" rel="noopener" title="{{ $ikon }}">
                                    <i class="bi bi-{{ $ikon }}"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------ asansor cumlesi --}}
        <div class="col-lg-8">
            <div class="card-ui mb-4">
                <div class="card-ui-head"><h2>3 dakikalik asansor cumlesi</h2></div>
                <div class="card-ui-body">

                    <div class="mb-4">
                        <div class="text-muted-2 mb-1" style="font-size:.8125rem;text-transform:uppercase;letter-spacing:.05em">
                            Hizmetlerimiz
                        </div>
                        <div style="white-space:pre-line">
                            {{ $uye->profile?->services_pitch ?: 'Bu uye henuz doldurmamis.' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-muted-2 mb-1" style="font-size:.8125rem;text-transform:uppercase;letter-spacing:.05em">
                            Arayislarimiz
                        </div>
                        <div style="white-space:pre-line">
                            {{ $uye->profile?->seeking_pitch ?: 'Bu uye henuz doldurmamis.' }}
                        </div>
                    </div>

                </div>
            </div>

            @if($uye->company)
                <div class="card-ui">
                    <div class="card-ui-head"><h2>{{ $uye->company->name }}</h2></div>
                    <div class="card-ui-body">
                        <div class="d-flex gap-3 align-items-start">
                            @if($uye->company->logo_path)
                                <img src="{{ asset($uye->company->logo_path) }}" alt="{{ $uye->company->name }}"
                                     style="width:80px;height:80px;object-fit:contain;flex:0 0 auto;
                                            border:1px solid var(--line);border-radius:8px;background:#fff">
                            @endif
                            <div style="min-width:0">
                                <div class="d-flex gap-1 flex-wrap mb-2">
                                    @if($uye->company->sector)
                                        <span class="badge-ui badge-nav">{{ $uye->company->sector }}</span>
                                    @endif
                                    @if($uye->company->city)
                                        <span class="badge-ui badge-nav">
                                            <i class="bi bi-geo-alt"></i> {{ $uye->company->city }}
                                        </span>
                                    @endif
                                    @if($uye->company->founded_on)
                                        <span class="badge-ui badge-nav">
                                            {{ $uye->company->founded_on->format('Y') }}'den beri
                                        </span>
                                    @endif
                                </div>

                                @if($uye->company->about)
                                    <p class="mb-2" style="white-space:pre-line">{{ $uye->company->about }}</p>
                                @endif

                                @if($uye->company->website)
                                    <a href="{{ $uye->company->website }}" target="_blank" rel="noopener"
                                       style="font-size:.9375rem">
                                        <i class="bi bi-box-arrow-up-right"></i> {{ $uye->company->website }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </div>

@endsection
