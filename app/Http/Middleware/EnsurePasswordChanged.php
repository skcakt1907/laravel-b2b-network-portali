<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gecici sifreyle giren uyeyi, sifresini degistirene kadar
 * baska hicbir sayfaya birakmaz.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $kullanici = $request->user();

        if ($kullanici === null || ! $kullanici->must_change_password) {
            return $next($request);
        }

        // Sifre degistirme ekraninin kendisi ve cikis serbest kalmali,
        // aksi halde sonsuz yonlendirme olusur.
        if ($request->routeIs('sifre.degistir', 'sifre.degistir.kaydet', 'cikis')) {
            return $next($request);
        }

        return redirect()->route('sifre.degistir')
            ->with('uyari', 'Devam etmeden once gecici sifrenizi degistirmeniz gerekiyor.');
    }
}
