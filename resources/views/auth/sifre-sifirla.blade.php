@extends('layouts.app')

@section('title', 'Yeni sifre belirle')
@section('subtitle', 'Sifre sifirlama')

@section('content')

    <form method="POST" action="{{ route('sifre.sifirla.kaydet') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label class="form-label" for="email">E-posta</label>
            <input class="form-control @error('email') is-invalid @enderror"
                   type="email" name="email" id="email"
                   value="{{ old('email', $email) }}"
                   required autocomplete="email">
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Yeni sifre</label>
            <input class="form-control @error('password') is-invalid @enderror"
                   type="password" name="password" id="password"
                   required autofocus autocomplete="new-password">
            <div class="form-hint">En az 8 karakter olmali.</div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password_confirmation">Yeni sifre (tekrar)</label>
            <input class="form-control" type="password"
                   name="password_confirmation" id="password_confirmation"
                   required autocomplete="new-password">
        </div>

        <button class="btn btn-primary w-100" type="submit">Sifreyi guncelle</button>
    </form>

@endsection
