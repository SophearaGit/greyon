<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Seeds the demo admin-panel accounts (developer lives in
 * DeveloperSeeder — a separate table now). Must run after
 * PackageSeeder. Demo emails match spec section 11's seed list, plus
 * one 2026-09-16 addition: `starter@` holds the capped `Admin ·
 * Starter` package (3 locations / 1 hotel each) so the package-limit
 * feature has a live account to test against, alongside `admin@`'s
 * uncapped `Admin · Full Suite` for regression checks.
 *
 * `locationIds`/`hotelIds` below are integers matching LocationSeeder/
 * HotelSeeder's rows (1 = Phnom Penh, 2 = Siem Reap for locations;
 * 1 = Riverside, 2 = Angkor for hotels) — passed to the package
 * *assignment* (the `admin_package` pivot), not stored on the admin
 * itself. Must run after both of those.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->makeAdmin('admin@greyon.com.kh', 'Admin · Full Suite', 'Admin · Full Suite');
        $this->makeAdmin('starter@greyon.com.kh', 'Admin · Starter', 'Admin · Starter');

        $this->makeAdmin('pp@greyon.com.kh', 'Phnom Penh Manager', 'Manager · Content+', locationIds: [1]);
        $this->makeAdmin('sr@greyon.com.kh', 'Siem Reap Manager', 'Manager · Content+', locationIds: [2]);

        $this->makeAdmin('hotel@greyon.com.kh', 'Hotel Admin · Riverside', 'Hotel Admin · Core', hotelIds: [1]);
        $this->makeAdmin('angkor@greyon.com.kh', 'Hotel Admin · Angkor', 'Hotel Admin · Core', hotelIds: [2]);
    }

    /**
     * @param  list<int>  $locationIds
     * @param  list<int>  $hotelIds
     */
    private function makeAdmin(string $email, string $name, string $packageName, array $locationIds = [], array $hotelIds = []): void
    {
        $admin = Admin::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $package = Package::where('name', $packageName)->firstOrFail();

        $admin->packages()->syncWithoutDetaching([
            $package->id => [
                'location_ids' => $locationIds ?: null,
                'hotel_ids' => $hotelIds ?: null,
            ],
        ]);
    }
}
