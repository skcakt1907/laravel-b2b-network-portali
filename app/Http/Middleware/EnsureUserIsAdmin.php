<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Yonetim alanini yalnizca admin rolune acar.
 *
 * Menuyu gizlemek yetmez: adres cubugena /yonetim yazan uye de durdurulmalidir.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $kullanici = $request->user();

        if ($kullanici === null || ! $kullanici->isAdmin()) {
            abort(403, 'Bu sayfaya erisim yetkiniz yok.');
        }

        return $next($request);
    }
}
