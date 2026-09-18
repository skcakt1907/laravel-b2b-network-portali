@extends('layouts.app')

@section('title', 'Erisim yok')
@section('subtitle', 'Yetkisiz islem')

@section('content')

    <div class="alert-ui alert-err mb-3">
        <i class="bi bi-slash-circle"></i>
        <div>{{ $exception?->getMessage() ?: 'Bu sayfaya erisim yetkiniz yok.' }}</div>
    </div>

    <p class="text-muted-2" style="font-size:.9375rem">
        Yanlis bir bag takip etmis olabilirsiniz. Erisiminiz olmasi gerektigini
        dusunuyorsaniz DN Unity yonetimiyle iletisime gecin.
    </p>

    @auth
        <a class="btn btn-primary w-100" href="{{ route('panel') }}">Panele don</a>
    @else
        <a class="btn btn-primary w-100" href="{{ route('giris') }}">Girise don</a>
    @endauth

@endsection
