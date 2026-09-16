<?php

namespace Database\Seeders;

use App\Models\Developer;
use Illuminate\Database\Seeder;

/**
 * Seeds the one and only developer account for local testing. Like
 * Admin, there's no public registration route — created via seeder or
 * `php artisan tinker` only.
 */
class DeveloperSeeder extends Seeder
{
    public function run(): void
    {
        Developer::firstOrCreate(
            ['email' => 'dev@greyon.com.kh'],
            [
                'name' => 'Platform Developer',
                'password' => 'password',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
