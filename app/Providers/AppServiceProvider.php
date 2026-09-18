<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Sifre sifirlama e-postasi Turkce ve DN Unity diliyle gonderilir
        ResetPassword::toMailUsing(function (object $kullanici, string $token) {
            $bag = URL::route('sifre.sifirla', [
                'token' => $token,
                'email' => $kullanici->getEmailForPasswordReset(),
            ], absolute: true);

            $dakika = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('DN Unity - Sifre sifirlama')
                ->greeting('Merhaba '.$kullanici->name.',')
                ->line('DN Unity hesabiniz icin sifre sifirlama talebi aldik.')
                ->action('Sifremi sifirla', $bag)
                ->line("Bu bag {$dakika} dakika sonra gecersiz olacaktir.")
                ->line('Bu talebi siz yapmadiysaniz herhangi bir islem yapmaniza gerek yok.')
                ->salutation('Saygilarimizla, DN Unity');
        });
    }
}
