@extends('layouts.app')

@section('title', 'Sifremi unuttum')
@section('subtitle', 'Sifre sifirlama bagi isteyin')

@section('content')

    <p class="text-muted-2 mb-3" style="font-size:.9375rem">
        Hesabinizin e-posta adresini girin; sifre belirlemeniz icin bir bag gonderelim.
    </p>

    <form method="POST" action="{{ route('sifremi.unuttum.gonder') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">E-posta</label>
            <input class="form-control @error('email') is-invalid @enderror"
                   type="email" name="email" id="email"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="email">
        </div>

        <button class="btn btn-primary w-100" type="submit">Sifirlama bagi gonder</button>
    </form>

    <p class="text-center mt-3 mb-0" style="font-size:.875rem">
        <a href="{{ route('giris') }}"><i class="bi bi-arrow-left"></i> Girise don</a>
    </p>

@endsection
