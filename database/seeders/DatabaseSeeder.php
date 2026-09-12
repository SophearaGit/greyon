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
        $this->call(AdminSeeder::class);

        // A guest customer you can log in with straight away.
        User::factory()->create([
            'name' => 'Test Guest',
            'email' => 'guest@greyon.test',
            'role' => 'guest',
        ]);

        // A hotel manager you can log in with straight away.
        User::factory()->manager()->create([
            'name' => 'Test Manager',
            'email' => 'manager@greyon.test',
            'role' => 'manager',
        ]);
    }
}
