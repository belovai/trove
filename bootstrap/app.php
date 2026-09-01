<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\Auth\Http\Middleware\EnsureUserIsNotBanned;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Self-hosted deployments almost always sit behind a TLS-terminating
        // reverse proxy (Traefik, nginx, Caddy), which speaks plain HTTP to the
        // app and reports the real scheme in X-Forwarded-Proto. Without a
        // trusted proxy Laravel ignores that header, and every absolute URL it
        // builds -- assets, redirects, signed media links -- comes out as
        // http://: the browser blocks the assets as mixed content and the
        // redirects point at a port the proxy does not serve.
        //
        // The default is '*' because the proxy's address differs in every
        // deployment and there is no value that would work out of the box
        // otherwise. That trusts X-Forwarded-* from any direct caller, so a
        // deployment where the container is reachable without going through the
        // proxy should pin TRUSTED_PROXIES to the proxy's address or CIDR --
        // otherwise a client could spoof its own IP for rate limiting and logs.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));

        $middleware->web(append: [
            SetLocale::class,
            EnsureUserIsNotBanned::class,
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
