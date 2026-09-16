<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seed the 3 conventional admin-side roles. `is_global`/`scope` are
 * what App\Services\AccessService actually reads to decide scoping —
 * nothing stops a developer from adding more roles later through
 * App\Http\Controllers\Developer\RoleController, with any `scope` they
 * like; these three are just what the app ships with.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Full access to every admin-panel feature.', 'is_global' => true, 'scope' => 'none']
        );

        Role::firstOrCreate(
            ['name' => 'manager'],
            ['description' => 'Scoped to the locations/destinations listed in locationIds.', 'is_global' => false, 'scope' => 'location']
        );

        Role::firstOrCreate(
            ['name' => 'hotel_admin'],
            ['description' => 'Scoped to the hotels listed in hotelIds.', 'is_global' => false, 'scope' => 'hotel']
        );
    }
}
