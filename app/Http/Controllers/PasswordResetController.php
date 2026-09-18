<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as SifreKurali;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showForgot(): View
    {
        return view('auth.sifremi-unuttum');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email']],
            [],
            ['email' => 'e-posta']
        );

        Password::sendResetLink($request->only('email'));

        // Hangi e-postanin kayitli oldugunu sizdirmamak icin
        // sonuc ne olursa olsun ayni mesaj doner.
        return back()->with(
            'basari',
            'Adres sistemde kayitliysa sifre sifirlama bagi e-posta ile gonderildi. Gelen kutunuzu kontrol edin.'
        );
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.sifre-sifirla', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', SifreKurali::min(8)],
        ], [], [
            'email' => 'e-posta',
            'password' => 'sifre',
        ]);

        $sonuc = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $kullanici, string $sifre) {
                $kullanici->forceFill([
                    'password' => $sifre,
                    'remember_token' => Str::random(60),
                    // Sifresini kendi belirledi; gecici sifre zorunlulugu kalkar
                    'must_change_password' => false,
                ])->save();

                event(new PasswordReset($kullanici));
            }
        );

        if ($sonuc !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => 'Sifre sifirlama bagi gecersiz veya suresi dolmus. Lutfen yeniden talep edin.',
            ]);
        }

        return redirect()->route('giris')
            ->with('basari', 'Sifreniz guncellendi. Yeni sifrenizle giris yapabilirsiniz.');
    }

    // ------------------------------------------------ zorunlu sifre degistirme

    public function showChange(): View
    {
        return view('auth.sifre-degistir');
    }

    public function change(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', SifreKurali::min(8), 'different:current_password'],
        ], [], [
            'current_password' => 'mevcut sifre',
            'password' => 'yeni sifre',
        ]);

        $request->user()->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('panel')
            ->with('basari', 'Sifreniz guncellendi. DN Unity ailesine hos geldiniz.');
    }
}
