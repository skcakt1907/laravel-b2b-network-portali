<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsMember;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Giris yapmamis ziyaretci varsayilan /login yerine Turkce rotaya gider
        $middleware->redirectGuestsTo(fn () => route('giris'));

        $middleware->web(append: [
            // Sira onemli: once hesap hala gecerli mi, sonra sifre zorunlulugu
            EnsureAccountActive::class,
            EnsurePasswordChanged::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'uye' => EnsureUserIsMember::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
