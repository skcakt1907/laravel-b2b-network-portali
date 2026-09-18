@extends('layouts.app')

@section('title', 'Giris')
@section('subtitle', 'Is ortaklari platformu')

@section('content')

    <form method="POST" action="{{ route('giris.yap') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">E-posta</label>
            <input class="form-control @error('email') is-invalid @enderror"
                   type="email" name="email" id="email"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="email">
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Sifre</label>
            <input class="form-control @error('password') is-invalid @enderror"
                   type="password" name="password" id="password"
                   required autocomplete="current-password">
        </div>

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1"
                       {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember" style="font-size:.875rem">Beni hatirla</label>
            </div>
            <a href="{{ route('sifremi.unuttum') }}" style="font-size:.875rem">Sifremi unuttum</a>
        </div>

        <button class="btn btn-primary w-100" type="submit">Giris yap</button>
    </form>

    <hr class="my-4" style="border-color:var(--line)">

    <p class="text-center text-muted-2 mb-0" style="font-size:.8125rem">
        <i class="bi bi-shield-lock"></i>
        DN Unity kapali devre bir platformdur. Uyelik yalnizca davet ve onay ile verilir.
    </p>

@endsection
