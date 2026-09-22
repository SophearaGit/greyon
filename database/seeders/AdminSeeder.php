<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Location;
use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Seeds the demo admin-panel accounts (developer lives in
 * DeveloperSeeder — a separate table now). Must run after
 * PackageSeeder and LocationSeeder.
 *
 * Round 12 (2026-09-22): matches the new 4-package lineup — one global
 * `Admin` (capped at 3 locations / 3 hotels per location) plus one
 * demo account per `Manager · <City>` package, each with `location_ids`
 * on the *assignment* resolved from the matching seeded Location so
 * the package's name and its actual scope always agree (see
 * PackageSeeder's docblock — the package itself doesn't carry a
 * location).
 *
 * Dropped from the previous lineup: `starter@` (the old, separately
 * capped admin tier no longer exists — `Admin` is capped by default
 * now), `sr@` (Siem Reap has no dedicated manager package in this
 * round's ask — the Siem Reap location itself is still seeded, just
 * not exclusively managed by a demo account anymore), `hotel@` /
 * `angkor@` (the `Hotel Admin · Core` package was dropped, not
 * replaced — the `hotel_admin` role still exists in RoleSeeder and a
 * developer can still build a package around it, it just has no seeded
 * demo package/account right now).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->makeAdmin('admin@greyon.com.kh', 'Admin', 'Admin');

        $this->makeAdmin('pp@greyon.com.kh', 'Phnom Penh Manager', 'Manager · Phnom Penh', locationSlug: 'phnom-penh');
        $this->makeAdmin('kp@greyon.com.kh', 'Kampot Manager', 'Manager · Kampot', locationSlug: 'kampot');
        $this->makeAdmin('sv@greyon.com.kh', 'Sihanoukville Manager', 'Manager · Sihanoukville', locationSlug: 'sihanoukville');
    }

    private function makeAdmin(string $email, string $name, string $packageName, ?string $locationSlug = null): void
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

        $locationIds = $locationSlug === null
            ? []
            : [Location::where('slug', $locationSlug)->firstOrFail()->id];

        $admin->packages()->syncWithoutDetaching([
            $package->id => [
                'location_ids' => $locationIds ?: null,
                'hotel_ids' => null,
            ],
        ]);
    }
}
