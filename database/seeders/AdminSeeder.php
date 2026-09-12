<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

/**
 * Seeds the one and only way an admin account gets created in this app
 * (besides `php artisan tinker`). There is no API endpoint that creates
 * an admin — see docs on the auth architecture, section 3.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::firstOrCreate(
            ['email' => 'admin@greyon.test'],
            [
                'name' => 'System Administrator',
                'password' => 'password',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
