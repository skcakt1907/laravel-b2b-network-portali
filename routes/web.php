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

    // Misafir de gorebilir (icerik ayrica EventPolicy ile suzulur)
    Route::view('/etkinlikler', 'panel.yapim-asamasi')->name('etkinlikler');
    Route::view('/profilim', 'panel.yapim-asamasi')->name('profilim');

    // ------------------------------------------------------------ uyelere ozel
    Route::middleware('uye')->group(function () {
        Route::view('/uyeler', 'panel.yapim-asamasi')->name('uyeler');
        Route::view('/cuzdan', 'panel.yapim-asamasi')->name('cuzdan');
    });

    // ------------------------------------------------------------ yonetim
    Route::middleware('admin')->prefix('yonetim')->name('yonetim.')->group(function () {
        Route::view('/', 'panel.yapim-asamasi')->name('index');
        Route::view('/basvurular', 'panel.yapim-asamasi')->name('basvurular');
        Route::view('/uyeler', 'panel.yapim-asamasi')->name('uyeler');
        Route::view('/etkinlikler', 'panel.yapim-asamasi')->name('etkinlikler');
        Route::view('/coin', 'panel.yapim-asamasi')->name('coin');
    });
});

// Gecici: tasarim sistemi on izlemesi. Yayina cikmadan once kaldirilacak.
if (config('app.debug')) {
    Route::view('/tasarim', 'tasarim');
}
