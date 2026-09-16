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
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $this->makePackage(
            'Admin · Full Suite',
            'Full admin-panel access.',
            null,
            ['admin'],
            Feature::where('category', 'admin')->pluck('key')->all(),
            permissionKeys: Permission::pluck('key')->all(),
        );

        $this->makePackage(
            'Manager · Content+',
            'Location-scoped content and bookings management.',
            null,
            ['manager'],
            ['dashboard', 'locations', 'hotels', 'rooms', 'rates', 'bookings', 'news'],
            permissionKeys: ['locations_list', 'locations_hotels'],
        );

        $this->makePackage(
            'Hotel Admin · Core',
            'Hotel-scoped rooms, rates and bookings.',
            null,
            ['hotel_admin'],
            ['dashboard', 'hotels', 'rooms', 'rates', 'bookings'],
        );

        // 2026-09-16: demonstrates App\Models\PackageLimit — a smaller,
        // capped admin tier alongside the uncapped Full Suite, not a
        // replacement for it. Same feature/permission set as Full Suite
        // (so the *only* thing this package demonstrates is the quota,
        // not a narrower grant set — that's a separate, orthogonal
        // design axis already covered by Manager · Content+/Hotel
        // Admin · Core) but capped to 3 locations total, 1 hotel per
        // location.
        $this->makePackage(
            'Admin · Starter',
            'Full admin-panel access, capped to 3 locations with 1 hotel each.',
            'Starter',
            ['admin'],
            Feature::where('category', 'admin')->pluck('key')->all(),
            limits: ['locations' => 3, 'hotels_per_location' => 1],
            permissionKeys: Permission::pluck('key')->all(),
        );
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
