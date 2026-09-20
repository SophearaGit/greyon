<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Org admins only — no Manager / Hotel desk seeds.
 * Testers log in as admin and create those seats + location/hotel scope in People.
 * Former package demos (pp@, kampot@, hotel@, …): docs/SEED_PACKAGE_USERS.md
 * Developer account: DeveloperSeeder.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->makeAdmin('admin@greyon.com.kh', 'Sovann Meas', 'Admin · Full Suite');
        $this->makeAdmin('starter@greyon.com.kh', 'Admin Starter', 'Admin · Starter');
    }

    private function makeAdmin(
        string $email,
        string $name,
        string $packageName,
    ): void {
        $admin = Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $package = Package::where('name', $packageName)->firstOrFail();

        $admin->packages()->sync([
            $package->id => [
                'location_ids' => null,
                'hotel_ids' => null,
            ],
        ]);
    }
}
