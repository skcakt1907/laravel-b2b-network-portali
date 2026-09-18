@extends('layouts.panel')

@section('title', 'Etkinlik yonetimi')

@section('content')

    <div class="card-ui">
        <div class="card-ui-head">
            <div class="d-flex gap-2">
                <a href="{{ route('yonetim.etkinlikler.index') }}"
                   class="btn btn-sm {{ $gecmis ? 'btn-outline' : 'btn-primary' }}">Yaklasan</a>
                <a href="{{ route('yonetim.etkinlikler.index', ['gecmis' => 1]) }}"
                   class="btn btn-sm {{ $gecmis ? 'btn-primary' : 'btn-outline' }}">Gecmis</a>
            </div>
            <a class="btn btn-gold btn-sm" href="{{ route('yonetim.etkinlikler.olustur') }}">
                <i class="bi bi-calendar-plus"></i> Yeni etkinlik
            </a>
        </div>

        <div class="card-ui-body p-0">
            @if($etkinlikler->isEmpty())
                <div class="empty">
                    <i class="bi bi-calendar-x"></i>
                    Etkinlik yok.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table-ui">
                        <thead>
                            <tr>
                                <th>Etkinlik</th>
                                <th>Tarih</th>
                                <th>Tur</th>
                                <th>Katilim</th>
                                <th>Durum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($etkinlikler as $e)
                                <tr>
                                    <td>
                                        <div style="font-weight:600">{{ $e->title }}</div>
                                        <div class="text-muted-2" style="font-size:.8125rem">
                                            {{ ['toplanti' => 'Toplanti', 'egitim' => 'Egitim', 'gezi' => 'Gezi', 'diger' => 'Diger'][$e->category] }}
                                            @if($e->has_presentations) &middot; sunum sirali @endif
                                        </div>
                                    </td>
                                    <td>{{ $e->starts_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <span class="badge-ui badge-nav">
                                            {{ $e->type === 'online' ? 'Online' : 'Fiziksel' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $e->katilan_sayisi }}@if($e->capacity) / {{ $e->capacity }}@endif
                                    </td>
                                    <td>
                                        @if($e->is_published)
                                            <span class="badge-ui badge-ok">Yayinda</span>
                                        @else
                                            <span class="badge-ui badge-warn">Taslak</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-outline btn-sm"
                                           href="{{ route('yonetim.etkinlikler.duzenle', $e) }}">Yonet</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if($etkinlikler->hasPages())
        <div class="mt-3">{{ $etkinlikler->links() }}</div>
    @endif

@endsection
