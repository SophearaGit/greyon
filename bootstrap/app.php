<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
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

        // Spec section 10: every error response is
        // { statusCode, message, error } — not Laravel's default
        // { message, errors } shape. One place to get this right
        // instead of hand-formatting it in every controller.
        $exceptions->render(function (Throwable $e, Request $request) {
            [$status, $message] = match (true) {
                $e instanceof ValidationException => [422, $e->getMessage()],
                $e instanceof AuthenticationException => [401, 'Unauthenticated.'],
                $e instanceof AuthorizationException => [403, $e->getMessage() ?: 'This action is unauthorized.'],
                $e instanceof ModelNotFoundException => [404, 'Not found.'],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), $e->getMessage() ?: null],
                default => [500, config('app.debug') ? $e->getMessage() : 'Server error.'],
            };

            $message ??= \Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ?? 'Error';

            $payload = [
                'statusCode' => $status,
                'message' => $message,
                'error' => \Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ?? 'Error',
            ];

            if ($e instanceof ValidationException) {
                $payload['errors'] = $e->errors();
            }

            return response()->json($payload, $status);
        });
    })->create();
