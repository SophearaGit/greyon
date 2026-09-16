<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Attach a package to the admin this state is applied to, once
     * created, with an optional per-assignment scope — e.g.
     * `Admin::factory()->withPackage('Manager · Content+', locationIds: [1])->create()`.
     * There's no `role`/`locationIds`/`hotelIds` column on `Admin`
     * itself to set in `definition()`; scope lives on the
     * `admin_package` pivot (App\Models\AdminPackage), which only
     * exists once both rows do — hence `afterCreating`, not a plain
     * state.
     *
     * @param  list<int>  $locationIds
     * @param  list<int>  $hotelIds
     */
    public function withPackage(string $packageName, array $locationIds = [], array $hotelIds = []): static
    {
        return $this->afterCreating(function (Admin $admin) use ($packageName, $locationIds, $hotelIds) {
            $package = Package::where('name', $packageName)->firstOrFail();

            $admin->packages()->syncWithoutDetaching([
                $package->id => [
                    'location_ids' => $locationIds ?: null,
                    'hotel_ids' => $hotelIds ?: null,
                ],
            ]);
        });
    }
}
