<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registered as the `role` middleware alias.
 *
 * Route usage: ->middleware(['auth', 'role:manager'])
 *
 * Always paired with `auth` (the `web` guard, i.e. the `users` table),
 * so `$request->user()` here is always a User — never an Admin, since
 * admin routes live behind the completely separate `admin` guard.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'Forbidden. Insufficient role.'], 403);
        }

        return $next($request);
    }
}
