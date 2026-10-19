<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter catalog for the new Services picker (client requirements
 * doc, 2026-10-04, item 6). Generic hotel/serviced-apartment services,
 * not tied to any one property — deliberately NOT attached to the real
 * Greyon SHV hotel here (see GreyonShvSeeder's own docblock for why
 * this seeder leaves real client data alone): whoever owns that
 * property's admin record should pick its actual services themselves
 * once this ships, not inherit a guessed list.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'Free WiFi',
            'Airport Transfer',
            'Daily Housekeeping',
            '24-Hour Front Desk',
            'Breakfast Included',
            'Swimming Pool',
            'Laundry Service',
            'Room Service',
            'Tour Desk / Travel Assistance',
            'Free Parking',
            'Spa & Wellness',
            'Airport Pickup',
        ];

        foreach ($services as $name) {
            Service::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'status' => 'published']
            );
        }
    }
}
