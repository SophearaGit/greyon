<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * 4 system packages an admin can be assigned (see AdminSeeder). System
 * packages can't be deleted (App\Http\Controllers\Developer\PackageController).
 *
 * Round 12 (2026-09-22) replaced the previous 4-package lineup (`Admin
 * · Full Suite`, `Admin · Starter`, `Manager · Content+`, `Hotel Admin
 * · Core`) outright, per the user's ask:
 *
 *   - "dev package with full access" maps to the separate `developers`
 *     guard/table (App\Models\Developer), which never goes through
 *     packages at all — it always has full, unconditional access (see
 *     AccessService's class docblock: "Developer accounts never go
 *     through here at all"). So there is deliberately no "dev" package
 *     here.
 *   - "admin package can add location and hotel limit of 3" is the
 *     `Admin` package below — every admin-category feature and every
 *     permission, but capped at 3 locations and 3 hotels per location.
 *     Unlike before this round, there is no longer an *uncapped* admin
 *     tier seeded — `Admin` is the only admin-role package, and it's
 *     capped by default.
 *   - **Added 2026-09-23**: the `Admin` package is also capped at 3
 *     people (`managers` limit) addable via People/Team
 *     (App\Http\Controllers\Admin\TeamController) — same "3, and we
 *     may raise it later" shape as the locations/hotels caps above.
 *     Raising it later is just editing this `PackageLimit` row (or
 *     via the developer package-builder UI) — nothing else to change.
 *   - "manager (city) — no adding properties, only others" is the 3
 *     `Manager · <City>` packages. A package doesn't carry a location
 *     itself — the actual scope comes from `location_ids` on that
 *     admin's package *assignment* (see AdminSeeder, and
 *     AccessService's class docblock) — so these 3 are functionally
 *     identical (same role, same features, same permissions, same
 *     limits) and differ only by name. They exist as 3 separate rows
 *     rather than 1 shared package purely so the developer-panel
 *     package picker reads unambiguously per city; assigning the wrong
 *     one of the 3 to an admin would carry the wrong label but not by
 *     itself grant the wrong location — that still depends on the
 *     `location_ids` set at assignment time.
 *   - **Clarified after the first pass (same day)**: "no adding
 *     properties" only meant *locations* (new destinations) — a
 *     manager *can* add hotels within their own location, capped at 3
 *     (same cap `Admin` gets). So `locations` stays capped at `0`
 *     (can't create a new destination at all) but
 *     `hotels_per_location` is `3`, not `0`.
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $adminFeatureKeys = Feature::where('category', 'admin')->pluck('key')->all();
        $allPermissionKeys = Permission::pluck('key')->all();

        $this->makePackage(
            'Admin',
            'Full admin-panel access, capped to 3 locations with 3 hotels each.',
            null,
            ['admin'],
            $adminFeatureKeys,
            limits: ['locations' => 3, 'hotels_per_location' => 3, 'managers' => 3],
            permissionKeys: $allPermissionKeys,
        );

        // Same role/features/permissions on all 3 — only the name (and,
        // at assignment time in AdminSeeder, the location_ids) differs.
        // `locations: 0` blocks creating a new *destination* outright —
        // AccessService::effectiveLimit() returns 0, and
        // LocationController blocks as soon as `used >= limit`, i.e.
        // immediately. `hotels_per_location: 3` means a manager *can*
        // add hotels within their own location — same cap `Admin` gets
        // — while every other locations/hotels capability (view, edit,
        // publish, SEO, and the rooms/rates/bookings/news that live
        // under them) is fully available either way. That's "no adding
        // [new] properties [destinations], only others [hotels + the
        // rest]" — clarified same-day after the first pass shipped with
        // `hotels_per_location: 0` too.
        $managerFeatureKeys = ['dashboard', 'locations', 'hotels', 'rooms', 'rates', 'bookings', 'news'];
        $managerLimits = ['locations' => 0, 'hotels_per_location' => 3];

        foreach (['Phnom Penh', 'Kampot', 'Sihanoukville'] as $city) {
            $this->makePackage(
                "Manager · {$city}",
                "Location-scoped content, rooms, rates, bookings and news for {$city} — can add up to 3 hotels here, cannot add new locations.",
                null,
                ['manager'],
                $managerFeatureKeys,
                limits: $managerLimits,
                permissionKeys: $allPermissionKeys,
            );
        }
    }

    /**
     * @param  list<string>  $roleNames
     * @param  list<string>  $featureKeys
     * @param  array<string, int>  $limits  resourceKey => maxCount
     * @param  list<string>  $permissionKeys
     */
    private function makePackage(
        string $name,
        string $description,
        ?string $priceNote,
        array $roleNames,
        array $featureKeys,
        array $limits = [],
        array $permissionKeys = [],
    ): void {
        $package = Package::firstOrCreate(
            ['name' => $name],
            ['description' => $description, 'price_note' => $priceNote, 'is_system' => true]
        );

        $package->roles()->sync(Role::whereIn('name', $roleNames)->pluck('id'));
        $package->features()->sync(Feature::whereIn('key', $featureKeys)->pluck('id'));
        $package->permissions()->sync(Permission::whereIn('key', $permissionKeys)->pluck('id'));

        foreach ($limits as $resourceKey => $maxCount) {
            $package->limits()->updateOrCreate(
                ['resource_key' => $resourceKey],
                ['max_count' => $maxCount],
            );
        }
    }
}
