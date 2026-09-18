<?php

use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
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

// ---------------------------------------------------------------- davet ile basvuru
// Girisli kullanici da bagi acabilir (davet bagini paylasan uye gibi);
// bu yuzden 'guest' grubunda degil.
Route::get('/basvuru/tesekkur', [ApplicationController::class, 'tesekkur'])
    ->name('davet.tesekkur');
Route::get('/davet/{code}', [ApplicationController::class, 'show'])
    ->name('davet.form');
Route::post('/davet/{code}', [ApplicationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('davet.gonder');

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

    // Misafir de kendi profilini duzenleyebilir
    Route::get('/profilim', [ProfileController::class, 'edit'])->name('profilim');
    Route::post('/profilim', [ProfileController::class, 'update'])->name('profilim.guncelle');

    // ------------------------------------------------------------ uyelere ozel
    Route::middleware('uye')->group(function () {
        Route::get('/uyeler', [DirectoryController::class, 'index'])->name('uyeler');
        Route::get('/uyeler/{uye}', [DirectoryController::class, 'show'])->name('uyeler.show');
        Route::view('/cuzdan', 'panel.yapim-asamasi')->name('cuzdan');
    });

    // ------------------------------------------------------------ yonetim
    Route::middleware('admin')->prefix('yonetim')->name('yonetim.')->group(function () {
        Route::view('/', 'panel.yapim-asamasi')->name('index');

        // Basvurular
        Route::get('/basvurular', [AdminApplicationController::class, 'index'])
            ->name('basvurular.index');
        Route::get('/basvurular/{basvuru}', [AdminApplicationController::class, 'show'])
            ->name('basvurular.show');
        Route::post('/basvurular/{basvuru}/onayla', [AdminApplicationController::class, 'approve'])
            ->name('basvurular.onayla');
        Route::post('/basvurular/{basvuru}/reddet', [AdminApplicationController::class, 'reject'])
            ->name('basvurular.reddet');

        // Davet baglari
        Route::get('/davetler', [InvitationController::class, 'index'])->name('davetler.index');
        Route::post('/davetler', [InvitationController::class, 'store'])->name('davetler.store');
        Route::delete('/davetler/{davet}', [InvitationController::class, 'destroy'])
            ->name('davetler.destroy');

        // Uye yonetimi
        Route::get('/uyeler', [AdminUserController::class, 'index'])->name('uyeler.index');
        Route::get('/uyeler/{uye}', [AdminUserController::class, 'show'])->name('uyeler.show');
        Route::post('/uyeler/{uye}/durum', [AdminUserController::class, 'durum'])
            ->name('uyeler.durum');
        Route::post('/uyeler/{uye}/anonimlestir', [AdminUserController::class, 'anonimlestir'])
            ->name('uyeler.anonimlestir');
        Route::view('/etkinlikler', 'panel.yapim-asamasi')->name('etkinlikler');
        Route::view('/coin', 'panel.yapim-asamasi')->name('coin');
    });
});

// Gecici: tasarim sistemi on izlemesi. Yayina cikmadan once kaldirilacak.
if (config('app.debug')) {
    Route::view('/tasarim', 'tasarim');
}
