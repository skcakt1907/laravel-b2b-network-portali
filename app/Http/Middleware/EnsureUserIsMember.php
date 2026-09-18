<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uye dizini, cuzdan gibi topluluga ozel alanlari misafirlere kapatir.
 *
 * Misafir yalnizca kendisine acilmis etkinlikleri ve kendi profilini gorebilir;
 * uye listesi ve Unitycoin onun icin yoktur.
 */
class EnsureUserIsMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $kullanici = $request->user();

        if ($kullanici === null || $kullanici->isMisafir()) {
            abort(403, 'Bu bolum yalnizca DN Unity uyelerine aciktir.');
        }

        return $next($request);
    }
}
