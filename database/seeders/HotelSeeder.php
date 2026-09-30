<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Empty catalog (2026-09-30): this used to seed 3 placeholder demo
 * hotels each for Phnom Penh and Kampot (Riverside Hotel, Capitol
 * Suites, Mekong House; Pepper House, Bokor View Lodge, Salt Field
 * Inn). Removed, along with their room types, rate plans, and demo
 * bookings, because mixing dummy hotels alongside the one real property
 * (Greyon Hotel & Serviced Apartment — see GreyonShvSeeder) made manual
 * testing confusing: it wasn't obvious which listings were real. Phnom
 * Penh and Kampot remain valid destinations with zero hotels, the same
 * way Sihanoukville started before GreyonShvSeeder existed — add a real
 * `$catalog` entry here (or a dedicated seeder, per GreyonShvSeeder's
 * pattern) once real property data is available for either.
 *
 * No `sihanoukville` entry either (removed 2026-09-29, before this):
 * see git history for that change.
 */
class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [];

        foreach ($catalog as $locationSlug => $hotels) {
            $location = Location::where('slug', $locationSlug)->firstOrFail();
            foreach ($hotels as $hotel) {
                Hotel::updateOrCreate(
                    ['slug' => $hotel['slug']],
                    [
                        'location_id' => $location->id,
                        'name' => $hotel['name'],
                        'short_description' => $hotel['short_description'],
                        'description' => $hotel['description'],
                        'address' => $hotel['address'],
                        'lat' => $hotel['lat'],
                        'lng' => $hotel['lng'],
                        'phone' => $hotel['phone'],
                        'email' => $hotel['email'],
                        'amenities' => $hotel['amenities'],
                        'hero_image' => $hotel['hero_image'],
                        'gallery' => $hotel['gallery'],
                        'check_in_time' => '14:00',
                        'check_out_time' => '12:00',
                        'featured' => $hotel['featured'],
                        'status' => 'published',
                        'seo_title' => "{$hotel['name']} | Greyon",
                        'seo_description' => "Book {$hotel['name']} with Greyon.",
                    ]
                );
            }
        }
    }
}
