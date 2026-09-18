@extends('layouts.app')

@section('title', 'Giris')
@section('subtitle', 'Kapali devre is ortaklari platformu')

@section('content')

    <div class="alert-ui alert-info mb-3">
        <i class="bi bi-shield-lock"></i>
        <div>
            DN Unity yalnizca davet edilen is ortaklarina aciktir.
            Uyelik basvurusu davet bagi ile alinir.
        </div>
    </div>

    {{-- Giris formu Faz 1 madde 4'te baglanacak; su an yalnizca gorunum --}}
    <form>
        <div class="mb-3">
            <label class="form-label" for="email">E-posta</label>
            <input class="form-control" type="email" id="email" placeholder="ornek@firma.com" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Sifre</label>
            <input class="form-control" type="password" id="password" placeholder="••••••••" disabled>
        </div>

        <button class="btn btn-primary w-100" type="button" disabled>Giris yap</button>
    </form>

    <p class="text-center mt-3 mb-0" style="font-size:.875rem">
        <a href="{{ url('/tasarim') }}">Tasarim sistemi on izlemesi</a>
    </p>

@endsection
