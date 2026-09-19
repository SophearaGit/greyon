<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Http\Resources\PackageResource;
use App\Models\Admin;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\Package;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Org-admin people management (feature: users).
 * Assigns seats (packages) that grant manager / hotel_admin — not other
 * org-admin seats. Scope: many locations per manager; hotels capped by
 * package limits (hotels_per_location).
 */
class TeamController extends Controller
{
    private const PACKAGE_EAGER_LOAD = ['packages.roles', 'packages.features', 'packages.permissions', 'packages.limits'];

    /** Roles an org admin may grant when creating people. */
    private const ASSIGNABLE_ROLES = ['manager', 'hotel_admin'];

    public function __construct(private readonly AccessService $access) {}

    public function seats(): JsonResponse
    {
        $packages = Package::query()
            ->with(['roles', 'features', 'permissions', 'limits'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::ASSIGNABLE_ROLES))
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
            ->orderBy('name')
            ->get();

        return response()->json(['seats' => PackageResource::collection($packages)]);
    }

    public function index(): JsonResponse
    {
        $admins = Admin::with(self::PACKAGE_EAGER_LOAD)->orderBy('name')->get();

        return response()->json(['admins' => AdminResource::collection($admins)]);
    }

    public function store(Request $request): JsonResponse
    {
        $actor = $request->user('admin');
        $data = $this->validated($request, null, $actor);

        $data['password'] = Hash::make($data['password'] ?? Str::password(16));
        $packageSync = $data['package_sync'];
        unset($data['package_sync']);

        $admin = Admin::create($data);
        $admin->packages()->sync($packageSync);

        return response()->json(['admin' => new AdminResource($admin->load(self::PACKAGE_EAGER_LOAD))], 201);
    }

    public function update(Request $request, Admin $target): JsonResponse
    {
        $actor = $request->user('admin');
        $this->assertEditable($actor, $target);

        $data = $this->validated($request, $target, $actor);

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

    public function destroy(Request $request, Admin $target): JsonResponse
    {
        $actor = $request->user('admin');
        $this->assertEditable($actor, $target);

        if ((int) $actor->id === (int) $target->id) {
            throw new HttpException(400, 'You cannot remove your own account.');
        }

        $target->delete();

        return response()->json(['message' => 'Person removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Admin $target, Admin $actor): array
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
            $data['package_sync'] = $this->resolvePackageSync($data['packages'], $actor);
            unset($data['packages']);
        }

        return $data;
    }

    /**
     * @param  list<array{packageId: int, locationIds?: list<int>, hotelIds?: list<int>}>  $entries
     * @return array<int, array{location_ids: ?list<int>, hotel_ids: ?list<int>}>
     */
    private function resolvePackageSync(array $entries, Admin $actor): array
    {
        $packageIds = array_column($entries, 'packageId');
        $packages = Package::whereIn('id', $packageIds)->with(['roles', 'limits'])->get()->keyBy('id');

        $unknown = array_diff($packageIds, $packages->keys()->all());
        if ($unknown !== []) {
            throw new HttpException(400, 'Unknown seat ids: '.implode(', ', $unknown));
        }

        $sync = [];

        foreach ($entries as $entry) {
            $package = $packages[$entry['packageId']];
            $roleNames = $package->roles->pluck('name');

            if ($roleNames->contains('admin')) {
                throw ValidationException::withMessages([
                    'packages' => 'Org admins cannot assign other admin seats. Pick a manager or hotel desk seat.',
                ]);
            }

            if ($roleNames->intersect(self::ASSIGNABLE_ROLES)->isEmpty()) {
                throw ValidationException::withMessages([
                    'packages' => "Seat \"{$package->name}\" is not assignable from People.",
                ]);
            }

            $roleScopes = $package->roles->pluck('scope')->unique();
            $locationIds = array_values(array_unique($entry['locationIds'] ?? []));
            $hotelIds = array_values(array_unique($entry['hotelIds'] ?? []));

            if ($roleScopes->contains('location') && $locationIds === []) {
                throw ValidationException::withMessages([
                    'packages' => "Pick at least one location for seat \"{$package->name}\".",
                ]);
            }

            if ($roleScopes->contains('hotel') && $hotelIds === []) {
                throw ValidationException::withMessages([
                    'packages' => "Pick at least one hotel for seat \"{$package->name}\".",
                ]);
            }

            $this->assertWithinLimits($actor, $package, $locationIds, $hotelIds);

            $sync[$package->id] = [
                'location_ids' => $locationIds ?: null,
                'hotel_ids' => $hotelIds ?: null,
            ];
        }

        return $sync;
    }

    /**
     * @param  list<int>  $locationIds
     * @param  list<int>  $hotelIds
     */
    private function assertWithinLimits(Admin $actor, Package $package, array $locationIds, array $hotelIds): void
    {
        $locCap = $this->access->effectiveLimit($actor, 'users', 'locations');
        if ($locCap === 0) {
            $locCap = $this->access->effectiveLimit($actor, 'locations', 'locations');
        }
        if ($locCap !== null && count($locationIds) > $locCap) {
            throw ValidationException::withMessages([
                'packages' => "Your seat allows at most {$locCap} locations per person.",
            ]);
        }

        $pkgLoc = $package->limits->firstWhere('resource_key', 'locations');
        if ($pkgLoc && count($locationIds) > (int) $pkgLoc->max_count) {
            throw ValidationException::withMessages([
                'packages' => "This seat allows at most {$pkgLoc->max_count} locations.",
            ]);
        }

        $hotelsPerLoc = $this->access->effectiveLimit($actor, 'users', 'hotels_per_location');
        if ($hotelsPerLoc === 0) {
            $hotelsPerLoc = $this->access->effectiveLimit($actor, 'hotels', 'hotels_per_location');
        }
        if ($hotelsPerLoc === null) {
            $hotelsPerLoc = optional($package->limits->firstWhere('resource_key', 'hotels_per_location'))->max_count;
        }

        if ($hotelsPerLoc !== null && $hotelIds !== []) {
            $byLocation = Hotel::whereIn('id', $hotelIds)
                ->get(['id', 'location_id'])
                ->groupBy('location_id');

            foreach ($byLocation as $locationId => $group) {
                if ($group->count() > (int) $hotelsPerLoc) {
                    $locName = Location::find($locationId)?->name ?? "#{$locationId}";
                    throw ValidationException::withMessages([
                        'packages' => "At most {$hotelsPerLoc} hotels per location ({$locName}).",
                    ]);
                }
            }
        }
    }

    private function assertEditable(Admin $actor, Admin $target): void
    {
        $roles = $this->access->effectiveRoles($target);
        if (in_array('admin', $roles, true) && (int) $actor->id !== (int) $target->id) {
            throw new HttpException(403, 'Org admins cannot edit other admin accounts.');
        }
    }
}
