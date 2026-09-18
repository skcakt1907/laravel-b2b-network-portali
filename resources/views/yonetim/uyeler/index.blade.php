@extends('layouts.panel')

@section('title', 'Uye yonetimi')
@section('subtitle', $uyeler->total().' kayit')

@section('content')

    <div class="card-ui">
        <div class="card-ui-head flex-wrap">
            <div class="d-flex gap-2 flex-wrap">
                @foreach ([
                    'hepsi' => 'Hepsi',
                    'aktif' => 'Aktif',
                    'donduruldu' => 'Dondurulmus',
                    'pasif' => 'Pasif',
                ] as $deger => $etiket)
                    <a href="{{ route('yonetim.uyeler.index', ['durum' => $deger, 'q' => $arama]) }}"
                       class="btn btn-sm {{ $durum === $deger ? 'btn-primary' : 'btn-outline' }}">
                        {{ $etiket }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('yonetim.uyeler.index') }}" class="d-flex gap-2">
                <input type="hidden" name="durum" value="{{ $durum }}">
                <input class="form-control form-control-sm" type="search" name="q"
                       value="{{ $arama }}" placeholder="Ad veya e-posta ara" style="min-width:200px">
                <button class="btn btn-outline btn-sm" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="card-ui-body p-0">
            @if($uyeler->isEmpty())
                <div class="empty">
                    <i class="bi bi-people"></i>
                    Bu filtrede uye yok.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table-ui">
                        <thead>
                            <tr>
                                <th>Uye</th>
                                <th>Firma</th>
                                <th>Rol</th>
                                <th>Durum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($uyeler as $uye)
                                <tr>
                                    <td>
                                        <div style="font-weight:600">{{ $uye->name }}</div>
                                        <div class="text-muted-2" style="font-size:.8125rem">{{ $uye->email }}</div>
                                    </td>
                                    <td>{{ $uye->company?->name ?: '—' }}</td>
                                    <td>
                                        <span class="badge-ui badge-nav">
                                            {{ ['admin' => 'Yonetici', 'uye' => 'Uye', 'misafir' => 'Misafir'][$uye->role] }}
                                        </span>
                                    </td>
                                    <td>@include('yonetim.uyeler.durum-rozeti', ['durum' => $uye->status])</td>
                                    <td class="text-end">
                                        <a class="btn btn-outline btn-sm"
                                           href="{{ route('yonetim.uyeler.show', $uye) }}">Ac</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if($uyeler->hasPages())
        <div class="mt-3">{{ $uyeler->links() }}</div>
    @endif

@endsection
