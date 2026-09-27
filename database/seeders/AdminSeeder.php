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
 * Main seat for the org admin is the live `Admin` package
 * (`packages.name = Admin`) — same row PackageSeeder upserts from the
 * database shape. City managers each get their matching
 * `Manager · <City>` package with `location_ids` on the assignment.
 *
 * Production-safe: existing accounts keep their password; only new
 * demo rows get `password`. Re-running still re-attaches the intended
 * main package (replaces the admin's package pivot).
 *
 * On production, prefer `PackageSeeder` alone unless you intentionally
 * want demo seats re-linked. Do not run full `db:seed` against prod.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Main org package — follow whatever PackageSeeder named "Admin".
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

        // Keep name/status in sync for demo seats without resetting password.
        if (! $admin->wasRecentlyCreated) {
            $admin->forceFill([
                'name' => $name,
                'status' => 'active',
                'email_verified_at' => $admin->email_verified_at ?? now(),
            ])->save();
        }

        $package = Package::where('name', $packageName)->firstOrFail();

        $locationIds = $locationSlug === null
            ? null
            : [Location::where('slug', $locationSlug)->firstOrFail()->id];

        // Replace seats so the seeded package is always the main (only) one.
        $admin->packages()->sync([
            $package->id => [
                'location_ids' => $locationIds,
                'hotel_ids' => null,
            ],
        ]);
    }
}
