<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Admin;
use App\Models\Developer;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Admin CMS controllers sit behind `auth:admin,developer` so a
 * developer session can manage destinations/hotels/rooms (and the rest
 * of the catalog) without holding an admin seat. Controllers that use
 * AccessService should call these helpers instead of assuming
 * `$request->user('admin')` is always present.
 *
 * Requires the host controller to expose `$this->access` (AccessService).
 */
trait AuthorizesAdminPanel
{
    protected function isDeveloper(Request $request): bool
    {
        return $request->user('developer') instanceof Developer;
    }

    protected function actingAdmin(Request $request): ?Admin
    {
        $admin = $request->user('admin');

        return $admin instanceof Admin ? $admin : null;
    }

    /**
     * Admin seat for AccessService calls — developers never reach here
     * for scope checks (see adminCanAccess*).
     */
    protected function requireAdmin(Request $request): Admin
    {
        $admin = $this->actingAdmin($request);
        if ($admin) {
            return $admin;
        }

        throw new HttpException(401, 'Unauthenticated.');
    }

    protected function adminCanAccessLocation(Request $request, mixed $locationId): bool
    {
        if ($this->isDeveloper($request)) {
            return true;
        }

        return $this->access->canAccessLocation($this->requireAdmin($request), $locationId);
    }

    protected function adminCanAccessHotel(Request $request, mixed $hotelId): bool
    {
        if ($this->isDeveloper($request)) {
            return true;
        }

        return $this->access->canAccessHotel($this->requireAdmin($request), $hotelId);
    }

    protected function assertGlobalSeat(Request $request): void
    {
        if ($this->isDeveloper($request)) {
            return;
        }

        if (! $this->access->isGlobal($this->requireAdmin($request))) {
            throw new HttpException(403, 'Only a global seat can do this.');
        }
    }
}
