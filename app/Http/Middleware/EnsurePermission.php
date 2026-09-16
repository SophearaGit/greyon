<?php

namespace App\Http\Middleware;

use App\Services\AccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Registered as the `permission` middleware alias.
 *
 * Route usage: ->middleware(['auth:admin', 'permission:hotels'])
 *
 * Always paired with `auth:admin`, so `$request->user('admin')` here is
 * always an Admin — this is the admin-panel permission gate, separate
 * from `role:` (App\Http\Middleware\EnsureUserHasRole), which guards
 * `users`/`web`-guard routes.
 */
class EnsurePermission
{
    public function __construct(private readonly AccessService $access) {}

    public function handle(Request $request, Closure $next, string $perm): Response
    {
        $admin = $request->user('admin');

        if (! $admin) {
            throw new HttpException(401, 'Unauthenticated.');
        }

        if (! $this->access->can($admin, $perm)) {
            throw new HttpException(403, "Missing permission: {$perm}");
        }

        return $next($request);
    }
}
