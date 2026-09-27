<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * System packages an admin can be assigned (see AdminSeeder). System
 * packages can't be deleted (App\Http\Controllers\Developer\PackageController).
 *
 * Shape mirrors the live local DB, with destination *create* denied on
 * every seeded seat (Admin + city managers):
 *
 *   - `Admin` — main org seat. Every admin feature except `settings`,
 *     plus public product surfaces. Caps: locations 0 (no new
 *     destinations), 3 hotels per location, 3 managers. Permission
 *     `locations_create` is intentionally withheld.
 *   - `Manager · <City>` × 3 — same feature set as live Manager rows,
 *     same "no create destination" rule (`locations` limit 0, no
 *     `locations_create`). Hotels still capped at 3 per location.
 *
 * Permissions are derived from the package's features, minus any
 * explicitly denied keys.
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        // Main org package — matches packages.id=1 in the live DB.
        // `settings` exists as a feature but is not on the Admin seat.
        $adminFeatureKeys = Feature::query()
            ->where(function ($q) {
                $q->where('category', 'admin')
                    ->where('key', '!=', 'settings');
            })
            ->orWhere('category', 'public')
            ->pluck('key')
            ->all();

        $this->makePackage(
            'Admin',
            'Full admin-panel access — can manage existing destinations and add up to 3 hotels each; cannot create new destinations.',
            null,
            ['admin'],
            $adminFeatureKeys,
            limits: ['locations' => 0, 'hotels_per_location' => 3, 'managers' => 3],
            denyPermissionKeys: ['locations_create'],
        );

        // City manager seats — feature set taken from live Manager · *
        // rows (enquiries / media / features included; no public keys,
        // no users / settings). Destination create denied same as Admin.
        $managerFeatureKeys = [
            'dashboard',
            'locations',
            'hotels',
            'rooms',
            'rates',
            'bookings',
            'news',
            'enquiries',
            'media',
            'features',
        ];
        $managerLimits = ['locations' => 0, 'hotels_per_location' => 3];

        foreach (['Phnom Penh', 'Kampot', 'Sihanoukville'] as $city) {
            $this->makePackage(
                "Manager · {$city}",
                "Location-scoped content, rooms, rates, bookings and news for {$city} — can add up to 3 hotels here, cannot create new destinations.",
                null,
                ['manager'],
                $managerFeatureKeys,
                limits: $managerLimits,
                denyPermissionKeys: ['locations_create'],
            );
        }
    }

    /**
     * @param  list<string>  $roleNames
     * @param  list<string>  $featureKeys
     * @param  array<string, int>  $limits  resourceKey => maxCount
     * @param  list<string>  $denyPermissionKeys  withheld even if under a granted feature
     */
    private function makePackage(
        string $name,
        string $description,
        ?string $priceNote,
        array $roleNames,
        array $featureKeys,
        array $limits = [],
        array $denyPermissionKeys = [],
    ): void {
        $package = Package::updateOrCreate(
            ['name' => $name],
            [
                'description' => $description,
                'price_note' => $priceNote,
                'is_system' => true,
            ]
        );

        $package->roles()->sync(Role::whereIn('name', $roleNames)->pluck('id'));
        $package->features()->sync(Feature::whereIn('key', $featureKeys)->pluck('id'));

        $permissionIds = Permission::query()
            ->whereHas('feature', fn ($q) => $q->whereIn('key', $featureKeys))
            ->when(
                $denyPermissionKeys !== [],
                fn ($q) => $q->whereNotIn('key', $denyPermissionKeys)
            )
            ->pluck('id');
        $package->permissions()->sync($permissionIds);

        $keepKeys = array_keys($limits);
        if ($keepKeys === []) {
            $package->limits()->delete();
        } else {
            $package->limits()->whereNotIn('resource_key', $keepKeys)->delete();
        }

        foreach ($limits as $resourceKey => $maxCount) {
            $package->limits()->updateOrCreate(
                ['resource_key' => $resourceKey],
                ['max_count' => $maxCount],
            );
        }
    }
}
