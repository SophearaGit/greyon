<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DeveloperSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(FeatureSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(PackageSeeder::class);
        $this->call(LocationSeeder::class);
        $this->call(HotelSeeder::class);
        $this->call(RoomTypeSeeder::class);
        $this->call(RatePlanSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(MediaSeeder::class);
        $this->call(NewsSeeder::class);
        $this->call(EnquirySeeder::class);

        // A guest customer you can log in with straight away.
        User::updateOrCreate(
            ['email' => 'guest@greyon.test'],
            [
                'name' => 'Test Guest',
                'role' => 'guest',
                'password' => 'password',
            ]
        );

        // A hotel manager you can log in with straight away.
        User::updateOrCreate(
            ['email' => 'manager@greyon.test'],
            [
                'name' => 'Test Manager',
                'role' => 'manager',
                'password' => 'password',
            ]
        );

        // Bookings after users exist so guest@greyon.test can own seed stays.
        $this->call(BookingSeeder::class);
    }
}
