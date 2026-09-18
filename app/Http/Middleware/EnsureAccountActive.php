<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Oturumu acik olan hesabin HER ISTEKTE hala gecerli oldugunu dogrular.
 *
 * Durum denetimi yalnizca giriste yapilirsa, dondurulan bir uye oturumu
 * acik oldugu surece iceride kalmaya devam eder. Dondurma isleminin
 * aninda etkili olmasi icin bu denetim her istekte tekrarlanir.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $kullanici = $request->user();

        if ($kullanici !== null && ! $kullanici->isAktif()) {
            $mesaj = $kullanici->isMisafir() && $kullanici->status === 'aktif'
                ? 'Misafir erisiminizin suresi doldu.'
                : 'Hesabiniz artik aktif degil. Lutfen yonetimle iletisime gecin.';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('giris')->with('uyari', $mesaj);
        }

        return $next($request);
    }
}
