<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check()
    ? redirect()->route('panel')
    : redirect()->route('giris'))->name('home');

// ---------------------------------------------------------------- misafir
Route::middleware('guest')->group(function () {
    Route::get('/giris', [AuthController::class, 'showLogin'])->name('giris');
    Route::post('/giris', [AuthController::class, 'login'])->name('giris.yap');

    Route::get('/sifremi-unuttum', [PasswordResetController::class, 'showForgot'])
        ->name('sifremi.unuttum');
    Route::post('/sifremi-unuttum', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:6,1')
        ->name('sifremi.unuttum.gonder');

    Route::get('/sifre-sifirla/{token}', [PasswordResetController::class, 'showReset'])
        ->name('sifre.sifirla');
    Route::post('/sifre-sifirla', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')
        ->name('sifre.sifirla.kaydet');
});

// ---------------------------------------------------------------- girisli
Route::middleware('auth')->group(function () {
    Route::post('/cikis', [AuthController::class, 'logout'])->name('cikis');

    // Gecici sifreyi degistirme (EnsurePasswordChanged bu rotalari serbest birakir)
    Route::get('/sifre-degistir', [PasswordResetController::class, 'showChange'])
        ->name('sifre.degistir');
    Route::post('/sifre-degistir', [PasswordResetController::class, 'change'])
        ->name('sifre.degistir.kaydet');

    Route::view('/panel', 'panel.index')->name('panel');
});

// Gecici: tasarim sistemi on izlemesi. Yayina cikmadan once kaldirilacak.
if (config('app.debug')) {
    Route::view('/tasarim', 'tasarim');
}
