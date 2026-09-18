@extends('layouts.app')

@section('title', 'Sifrenizi belirleyin')
@section('subtitle', 'Ilk giris')

@section('content')

    <div class="alert-ui alert-info mb-3">
        <i class="bi bi-key"></i>
        <div>Size gonderilen gecici sifreyi kendi belirleyeceginiz bir sifreyle degistirin.</div>
    </div>

    <form method="POST" action="{{ route('sifre.degistir.kaydet') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="current_password">Gecici sifre</label>
            <input class="form-control @error('current_password') is-invalid @enderror"
                   type="password" name="current_password" id="current_password"
                   required autofocus autocomplete="current-password">
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Yeni sifre</label>
            <input class="form-control @error('password') is-invalid @enderror"
                   type="password" name="password" id="password"
                   required autocomplete="new-password">
            <div class="form-hint">En az 8 karakter olmali ve gecici sifreden farkli olmali.</div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password_confirmation">Yeni sifre (tekrar)</label>
            <input class="form-control" type="password"
                   name="password_confirmation" id="password_confirmation"
                   required autocomplete="new-password">
        </div>

        <button class="btn btn-primary w-100" type="submit">Sifreyi kaydet ve devam et</button>
    </form>

    <form method="POST" action="{{ route('cikis') }}" class="mt-3 text-center">
        @csrf
        <button class="btn btn-ghost btn-sm" type="submit">Cikis yap</button>
    </form>

@endsection
