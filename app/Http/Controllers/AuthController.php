<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Ayni e-posta + IP icin izin verilen ardisik basarisiz deneme sayisi. */
    private const DENEME_SINIRI = 5;

    private const KILIT_SANIYE = 60;

    public function showLogin(): View
    {
        return view('auth.giris');
    }

    public function login(Request $request): RedirectResponse
    {
        $veri = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], [
            'email' => 'e-posta',
            'password' => 'sifre',
        ]);

        $anahtar = $this->kilitAnahtari($request);

        // Kaba kuvvet denemelerine karsi: ayni e-posta+IP icin hiz siniri
        if (RateLimiter::tooManyAttempts($anahtar, self::DENEME_SINIRI)) {
            $kalan = RateLimiter::availableIn($anahtar);

            throw ValidationException::withMessages([
                'email' => "Cok fazla basarisiz deneme yaptiniz. {$kalan} saniye sonra tekrar deneyin.",
            ]);
        }

        if (! Auth::attempt($veri, $request->boolean('remember'))) {
            RateLimiter::hit($anahtar, self::KILIT_SANIYE);

            throw ValidationException::withMessages([
                'email' => 'E-posta veya sifre hatali.',
            ]);
        }

        $kullanici = Auth::user();

        // Kapali devre: onaysiz, dondurulmus veya suresi dolmus hesap giremez
        if (! $kullanici->isAktif()) {
            $mesaj = $this->durumMesaji($kullanici);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages(['email' => $mesaj]);
        }

        RateLimiter::clear($anahtar);

        // Oturum sabitleme saldirisina karsi oturum kimligi yenilenir
        $request->session()->regenerate();

        $kullanici->forceFill(['last_login_at' => now()])->save();

        // Gecici sifreyle giren once sifresini degistirmek zorunda
        if ($kullanici->must_change_password) {
            return redirect()->route('sifre.degistir');
        }

        return redirect()->intended(route('panel'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('giris')->with('bilgi', 'Cikis yapildi.');
    }

    private function durumMesaji($kullanici): string
    {
        return match (true) {
            $kullanici->status === 'beklemede' => 'Uyelik basvurunuz henuz onaylanmadi. Onaylandiginda e-posta ile bilgilendirileceksiniz.',
            $kullanici->status === 'donduruldu' => 'Hesabiniz gecici olarak donduruldu. Lutfen yonetimle iletisime gecin.',
            $kullanici->status === 'pasif' => 'Hesabiniz kapatilmis. Lutfen yonetimle iletisime gecin.',
            default => 'Misafir erisiminizin suresi dolmus.',
        };
    }

    private function kilitAnahtari(Request $request): string
    {
        return 'giris|'.Str::lower($request->input('email')).'|'.$request->ip();
    }
}
