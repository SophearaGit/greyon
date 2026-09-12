<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // This app has no Blade frontend of its own — every client is
        // Postman (or a future mobile app) sending JSON directly, never
        // a browser page that could be tricked into submitting a form
        // here. CSRF tokens exist to stop exactly that browser-form
        // scenario, so with no browser frontend in the picture they add
        // friction (fetching + resending a token on every request)
        // without protecting against anything. Session-cookie auth is
        // still real auth: a request still needs a valid session cookie
        // (returned only by /login or /admin/login) to reach anything
        // behind `auth`/`auth:admin`.
        $middleware->validateCsrfTokens(except: ['*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();
