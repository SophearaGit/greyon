<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Admin-panel account management — developer-only, gated purely by the
 * `auth:developer` guard on the whole `developer.php` route group (no
 * per-account "are you actually a developer" check needed the way the
 * old role-column version needed one, since only a real `Developer`
 * account can authenticate on this guard at all).
 *
 * "developer manage everything, add package... for each user [admin]" —
 * `packages` here is how a developer grants an admin its
 * roles/features (see App\Services\AccessService), and each entry in
 * it is one package assignment carrying its *own* `locationIds`/
 * `hotelIds` scope (stored on the `admin_package` pivot — see
 * App\Models\AdminPackage) — not a flat pair on the admin itself. An
 * admin's only real attributes here are its packages: name/email/
 * password/status plus "which packages, each scoped how." Each of
 * `locationIds`/`hotelIds` is a plain array, not a single id — a
 * developer can hand one location-scoped assignment several locations
 * at once (Phnom Penh + Sihanoukville + Kep, say), and every id is
 * validated to actually exist (`Rule::exists`) before it's stored.
 */
class AdminController extends Controller
{
    private const PACKAGE_EAGER_LOAD = ['packages.roles', 'packages.features', 'packages.permissions'];

    public function index(): JsonResponse
    {
        $admins = Admin::with(self::PACKAGE_EAGER_LOAD)->orderBy('name')->get();

        return response()->json(['admins' => AdminResource::collection($admins)]);
    }

    public function show(Admin $target): JsonResponse
    {
        return response()->json(['admin' => new AdminResource($target->load(self::PACKAGE_EAGER_LOAD))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $data['password'] = Hash::make($data['password'] ?? Str::password(16));

        $packageSync = $data['package_sync'];
        unset($data['package_sync']);

        $admin = Admin::create($data);
        $admin->packages()->sync($packageSync);

        return response()->json(['admin' => new AdminResource($admin->load(self::PACKAGE_EAGER_LOAD))], 201);
    }

    public function update(Request $request, Admin $target): JsonResponse
    {
        $data = $this->validated($request, $target);

        if (array_key_exists('password', $data)) {
            $data['password'] = filled($data['password']) ? Hash::make($data['password']) : $target->password;
        }

        $packageSync = $data['package_sync'] ?? null;
        unset($data['package_sync']);

        $target->update($data);

        if ($packageSync !== null) {
            $target->packages()->sync($packageSync);
        }

        return response()->json(['admin' => new AdminResource($target->load(self::PACKAGE_EAGER_LOAD))]);
    }

    public function destroy(Admin $target): JsonResponse
    {
        $target->delete();

        return response()->json(['message' => 'Admin deleted.']);
    }

    /**
     * `PUT /developer/admins/:id/packages` — replaces the whole set of
     * package assignments (and each one's scope) in one call. Body:
     * `{ packages: [ { packageId, locationIds?, hotelIds? }, ... ] }`.
     * `locationIds`/`hotelIds` are plain arrays with no length limit —
     * a developer can grant one location-scoped package assignment
     * several locations at once (e.g. `[1, 3, 4]` for Phnom Penh +
     * Sihanoukville + Kep), not just one; `AccessService` already
     * checks *membership* in the array everywhere, not equality
     * against a single id.
     */
    public function updatePackages(Request $request, Admin $target): JsonResponse
    {
        $data = $request->validate([
            'packages' => ['required', 'array'],
            'packages.*.packageId' => ['required', 'integer'],
            'packages.*.locationIds' => ['array'],
            'packages.*.locationIds.*' => ['integer', Rule::exists(Location::class, 'id')],
            'packages.*.hotelIds' => ['array'],
            'packages.*.hotelIds.*' => ['integer', Rule::exists(Hotel::class, 'id')],
        ]);

        $target->packages()->sync($this->resolvePackageSync($data['packages']));

        return response()->json(['admin' => new AdminResource($target->load(self::PACKAGE_EAGER_LOAD))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Admin $target = null): array
    {
        $data = $request->validate([
            'name' => [$target ? 'sometimes' : 'required', 'string', 'max:255'],
            'email' => [
                $target ? 'sometimes' : 'required',
                'email',
                Rule::unique('admins', 'email')->ignore($target?->id),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'packages' => [$target ? 'sometimes' : 'required', 'array', 'min:1'],
            'packages.*.packageId' => ['required', 'integer'],
            'packages.*.locationIds' => ['array'],
            'packages.*.locationIds.*' => ['integer', Rule::exists(Location::class, 'id')],
            'packages.*.hotelIds' => ['array'],
            'packages.*.hotelIds.*' => ['integer', Rule::exists(Hotel::class, 'id')],
        ]);

        if (array_key_exists('packages', $data)) {
            $data['package_sync'] = $this->resolvePackageSync($data['packages']);
            unset($data['packages']);
        }

        return $data;
    }

    /**
     * Resolves `{ packageId, locationIds?, hotelIds? }[]` into the
     * `sync()`-ready pivot map `[packageId => ['location_ids' => ...,
     * 'hotel_ids' => ...]]`, validating each entry's scope against that
     * *specific* package's granted roles as it goes — spec parity:
     * "effective roles include a location-scoped role → locationIds
     * required (for that package's assignment)," same for a
     * hotel-scoped role/hotelIds. Checked against each role's `scope`
     * column (App\Models\Role), not a hard-coded name, so this keeps
     * working for any role a developer adds later with
     * `scope: 'location'`/`'hotel'`.
     *
     * @param  list<array{packageId: int, locationIds?: list<int>, hotelIds?: list<int>}>  $entries
     * @return array<int, array{location_ids: ?list<int>, hotel_ids: ?list<int>}>
     */
    private function resolvePackageSync(array $entries): array
    {
        $packageIds = array_column($entries, 'packageId');

        $packages = Package::whereIn('id', $packageIds)->with('roles')->get()->keyBy('id');

        $unknown = array_diff($packageIds, $packages->keys()->all());

        if ($unknown !== []) {
            throw new HttpException(400, 'Unknown package ids: '.implode(', ', $unknown));
        }

        $sync = [];

        foreach ($entries as $entry) {
            $package = $packages[$entry['packageId']];
            $roleScopes = $package->roles->pluck('scope')->unique();
            $locationIds = $entry['locationIds'] ?? [];
            $hotelIds = $entry['hotelIds'] ?? [];

            if ($roleScopes->contains('location') && $locationIds === []) {
                throw ValidationException::withMessages([
                    'packages' => "locationIds is required for package \"{$package->name}\" — it grants a location-scoped role.",
                ]);
            }

            if ($roleScopes->contains('hotel') && $hotelIds === []) {
                throw ValidationException::withMessages([
                    'packages' => "hotelIds is required for package \"{$package->name}\" — it grants a hotel-scoped role.",
                ]);
            }

            $sync[$package->id] = [
                'location_ids' => $locationIds ?: null,
                'hotel_ids' => $hotelIds ?: null,
            ];
        }

        return $sync;
    }
}
