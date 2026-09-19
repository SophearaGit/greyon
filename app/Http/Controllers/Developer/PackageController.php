<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * "crud package for developer to add for user [admin]" — a package
 * bundles roles + features (+ permissions, 2026-09-16); developer
 * assigns packages to admins (see AdminController::updatePackages).
 * Body shape matches the original handoff spec's example as closely as
 * our dynamic roles/features entities allow: `roles` is an array of
 * role *names* (not ids) and `featureKeys` an array of feature *keys*,
 * same as the spec — "Unknown feature keys → 400" (and the same for
 * roles) is enforced the same way the spec asked for it.
 *
 * `permissionKeys` (2026-09-16, the doc maker's "package -> permissions"
 * sample) is a fourth, optional array of `App\Models\Permission` keys —
 * same "unknown key → 400" convention, same "`PATCH` replaces the
 * whole set" convention as `featureKeys`. **Not** automatically implied
 * by `featureKeys`: granting the `locations` feature does not also
 * grant any of its catalogued permissions — each has to be listed
 * explicitly here too, same as Round 8's sub-feature keys worked
 * before this table existed. See
 * App\Services\AccessService::effectivePermissionKeys().
 *
 * `limits` (2026-09-16) is a third, optional array on this same body:
 * `[{ resourceKey, maxCount }]`, one row per `App\Models\PackageLimit`
 * this package should have. `maxCount: null` (or simply omitting a key
 * that previously had a row) means unlimited for that key — see
 * App\Services\AccessService::effectiveLimit() for how a held
 * package's limits (or lack of them) combine into an admin's effective
 * cap. Like `featureKeys`, a `PATCH` with `limits` present **replaces**
 * the package's whole limit set, it doesn't merge.
 */
class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::withCount('admins')
            ->with(['roles', 'features', 'permissions', 'limits'])
            ->orderBy('name')
            ->get();

        return response()->json(['packages' => PackageResource::collection($packages)]);
    }

    public function show(Package $package): JsonResponse
    {
        $package->load(['roles', 'features', 'permissions', 'limits'])->loadCount('admins');

        return response()->json(['package' => new PackageResource($package)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priceNote' => ['nullable', 'string', 'max:255'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string'],
            'featureKeys' => ['array'],
            'featureKeys.*' => ['string'],
            'permissionKeys' => ['array'],
            'permissionKeys.*' => ['string'],
            'limits' => ['array'],
            'limits.*.resourceKey' => ['required_with:limits', 'string', 'max:255'],
            'limits.*.maxCount' => ['nullable', 'integer', 'min:0'],
        ]);

        $roleIds = $this->resolveRoleIds($data['roles']);
        $featureIds = $this->resolveFeatureIds($data['featureKeys'] ?? []);
        $permissionIds = $this->resolvePermissionIds($data['permissionKeys'] ?? []);

        $package = Package::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_note' => $data['priceNote'] ?? null,
        ]);

        $package->roles()->sync($roleIds);
        $package->features()->sync($featureIds);
        $package->permissions()->sync($permissionIds);
        $this->syncLimits($package, $data['limits'] ?? []);

        return response()->json(['package' => new PackageResource($package->load(['roles', 'features', 'permissions', 'limits']))], 201);
    }

    public function update(Request $request, Package $package): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priceNote' => ['nullable', 'string', 'max:255'],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['string'],
            'featureKeys' => ['sometimes', 'array'],
            'featureKeys.*' => ['string'],
            'permissionKeys' => ['sometimes', 'array'],
            'permissionKeys.*' => ['string'],
            'limits' => ['sometimes', 'array'],
            'limits.*.resourceKey' => ['required_with:limits', 'string', 'max:255'],
            'limits.*.maxCount' => ['nullable', 'integer', 'min:0'],
        ]);

        $package->update([
            'name' => $data['name'] ?? $package->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $package->description,
            'price_note' => $data['priceNote'] ?? $package->price_note,
        ]);

        if (array_key_exists('roles', $data)) {
            $package->roles()->sync($this->resolveRoleIds($data['roles']));
        }

        if (array_key_exists('featureKeys', $data)) {
            $package->features()->sync($this->resolveFeatureIds($data['featureKeys']));
        }

        if (array_key_exists('permissionKeys', $data)) {
            $package->permissions()->sync($this->resolvePermissionIds($data['permissionKeys']));
        }

        if (array_key_exists('limits', $data)) {
            $this->syncLimits($package, $data['limits']);
        }

        return response()->json(['package' => new PackageResource($package->load(['roles', 'features', 'permissions', 'limits']))]);
    }

    public function destroy(Package $package): JsonResponse
    {
        if ($package->is_system) {
            throw new HttpException(400, 'Cannot delete a system package.');
        }

        if ($package->admins()->exists()) {
            throw new HttpException(400, 'Cannot delete a package that admins are currently assigned. Reassign them first.');
        }

        $package->delete();

        return response()->json(['message' => 'Package deleted.']);
    }

    public function duplicate(Package $package): JsonResponse
    {
        $copy = Package::create([
            'name' => $package->name.' (copy)',
            'description' => $package->description,
            'price_note' => $package->price_note,
            'is_system' => false,
        ]);

        $copy->roles()->sync($package->roles()->pluck('roles.id'));
        $copy->features()->sync($package->features()->pluck('features.id'));
        $copy->permissions()->sync($package->permissions()->pluck('permissions.id'));

        foreach ($package->limits as $limit) {
            $copy->limits()->create([
                'resource_key' => $limit->resource_key,
                'max_count' => $limit->max_count,
            ]);
        }

        return response()->json(['package' => new PackageResource($copy->load(['roles', 'features', 'permissions', 'limits']))], 201);
    }

    /**
     * Replace this package's whole `limits` set (same "PATCH replaces"
     * convention as `roles`/`featureKeys` above) — delete every
     * existing row and recreate from `$limits`. Duplicate
     * `resourceKey`s in the same request are rejected with a 400
     * naming them, same spirit as `resolveRoleIds()`/
     * `resolveFeatureIds()`'s "unknown X → 400": a silent last-one-wins
     * would hide a client bug instead of surfacing it.
     *
     * @param  list<array{resourceKey: string, maxCount: int|null}>  $limits
     */
    private function syncLimits(Package $package, array $limits): void
    {
        $keys = array_column($limits, 'resourceKey');
        $duplicates = array_unique(array_diff_assoc($keys, array_unique($keys)));

        if ($duplicates !== []) {
            throw new HttpException(400, 'Duplicate resourceKey in limits: '.implode(', ', $duplicates));
        }

        $package->limits()->delete();

        foreach ($limits as $limit) {
            $package->limits()->create([
                'resource_key' => $limit['resourceKey'],
                'max_count' => $limit['maxCount'] ?? null,
            ]);
        }
    }

    /**
     * @param  list<string>  $names
     * @return list<int>
     */
    private function resolveRoleIds(array $names): array
    {
        $roles = Role::whereIn('name', $names)->get();

        $unknown = array_diff($names, $roles->pluck('name')->all());

        if ($unknown !== []) {
            throw new HttpException(400, 'Unknown role names: '.implode(', ', $unknown));
        }

        return $roles->pluck('id')->all();
    }

    /**
     * @param  list<string>  $keys
     * @return list<int>
     */
    private function resolveFeatureIds(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $features = Feature::whereIn('key', $keys)->get();

        $unknown = array_diff($keys, $features->pluck('key')->all());

        if ($unknown !== []) {
            throw new HttpException(400, 'Unknown feature keys: '.implode(', ', $unknown));
        }

        return $features->pluck('id')->all();
    }

    /**
     * @param  list<string>  $keys
     * @return list<int>
     */
    private function resolvePermissionIds(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $permissions = Permission::whereIn('key', $keys)->get();

        $unknown = array_diff($keys, $permissions->pluck('key')->all());

        if ($unknown !== []) {
            throw new HttpException(400, 'Unknown permission keys: '.implode(', ', $unknown));
        }

        return $permissions->pluck('id')->all();
    }
}
