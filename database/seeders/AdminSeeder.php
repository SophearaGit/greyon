<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Demo admins for the 3-destination model.
 * Managers may hold many locations; hotel admins hold hotels.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $pp = Location::where('slug', 'phnom-penh')->firstOrFail();
        $shv = Location::where('slug', 'sihanoukville')->firstOrFail();
        $kampot = Location::where('slug', 'kampot')->firstOrFail();

        $riverside = Hotel::where('slug', 'riverside')->firstOrFail();
        $otres = Hotel::where('slug', 'otres-bay')->firstOrFail();
        $pepper = Hotel::where('slug', 'pepper-house')->firstOrFail();

        $this->makeAdmin('admin@greyon.com.kh', 'Sovann Meas', 'Admin · Full Suite');
        $this->makeAdmin('starter@greyon.com.kh', 'Admin Starter', 'Admin · Starter');

        // One manager can own several destinations.
        $this->makeAdmin(
            'pp@greyon.com.kh',
            'Capital Coast Manager',
            'Manager · Booking Pro',
            locationIds: [$pp->id, $shv->id],
        );
        $this->makeAdmin(
            'kampot@greyon.com.kh',
            'Kampot Manager',
            'Manager · Content+',
            locationIds: [$kampot->id],
        );

        $this->makeAdmin(
            'hotel@greyon.com.kh',
            'Riverside Front Desk',
            'Hotel Admin · Core',
            hotelIds: [$riverside->id],
        );
        $this->makeAdmin(
            'otres@greyon.com.kh',
            'Otres Front Desk',
            'Hotel Admin · Booking Pro',
            hotelIds: [$otres->id],
        );
        $this->makeAdmin(
            'pepper@greyon.com.kh',
            'Pepper House Desk',
            'Hotel Admin · Core',
            hotelIds: [$pepper->id],
        );
    }

    /**
     * @param  list<int>  $locationIds
     * @param  list<int>  $hotelIds
     */
    private function makeAdmin(
        string $email,
        string $name,
        string $packageName,
        array $locationIds = [],
        array $hotelIds = [],
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
                'location_ids' => $locationIds ?: null,
                'hotel_ids' => $hotelIds ?: null,
            ],
        ]);
    }
}
