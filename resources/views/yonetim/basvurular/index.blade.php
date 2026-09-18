@extends('layouts.panel')

@section('title', 'Basvurular')
@section('subtitle', $bekleyenSayisi.' basvuru incelenmeyi bekliyor.')

@section('content')

    <div class="card-ui">
        <div class="card-ui-head">
            <div class="d-flex gap-2 flex-wrap">
                @foreach ([
                    'beklemede' => 'Bekleyen',
                    'onaylandi' => 'Onaylanan',
                    'reddedildi' => 'Reddedilen',
                    'hepsi' => 'Hepsi',
                ] as $deger => $etiket)
                    <a href="{{ route('yonetim.basvurular.index', ['durum' => $deger]) }}"
                       class="btn btn-sm {{ $durum === $deger ? 'btn-primary' : 'btn-outline' }}">
                        {{ $etiket }}
                    </a>
                @endforeach
            </div>
            <a class="btn btn-gold btn-sm" href="{{ route('yonetim.davetler.index') }}">
                <i class="bi bi-envelope-plus"></i> Davet bagi olustur
            </a>
        </div>

        <div class="card-ui-body p-0">
            @if($basvurular->isEmpty())
                <div class="empty">
                    <i class="bi bi-inbox"></i>
                    Bu filtrede basvuru yok.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table-ui">
                        <thead>
                            <tr>
                                <th>Aday</th>
                                <th>Firma</th>
                                <th>Davet eden</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($basvurular as $basvuru)
                                <tr>
                                    <td>
                                        <div style="font-weight:600">{{ $basvuru->name }}</div>
                                        <div class="text-muted-2" style="font-size:.8125rem">{{ $basvuru->email }}</div>
                                    </td>
                                    <td>
                                        {{ $basvuru->company_name ?: '—' }}
                                        @if($basvuru->city)
                                            <div class="text-muted-2" style="font-size:.8125rem">{{ $basvuru->city }}</div>
                                        @endif
                                    </td>
                                    <td class="text-muted-2" style="font-size:.875rem">
                                        {{ $basvuru->invitation?->inviter?->name ?? '—' }}
                                    </td>
                                    <td class="text-muted-2" style="font-size:.875rem">
                                        {{ $basvuru->created_at->format('d.m.Y') }}
                                    </td>
                                    <td>
                                        @include('yonetim.basvurular.durum-rozeti', ['durum' => $basvuru->status])
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-outline btn-sm"
                                           href="{{ route('yonetim.basvurular.show', $basvuru) }}">Incele</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if($basvurular->hasPages())
        <div class="mt-3">{{ $basvurular->links() }}</div>
    @endif

@endsection
