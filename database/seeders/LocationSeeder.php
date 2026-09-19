<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Greyon operates three destinations. Hotel cap is enforced via package
 * limits (hotels_per_location = 3), not by this seeder alone.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'slug' => 'phnom-penh',
                'name' => 'Phnom Penh',
                'description' => 'The capital — riverside hotels, city tours, and business stays.',
                'highlights' => ['Riverside promenade', 'Royal Palace', 'Central Market'],
                'hero_image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=80',
                'seo_title' => 'Hotels in Phnom Penh | Greyon',
                'seo_description' => 'Book hotels in Phnom Penh, Cambodia\'s capital city.',
            ],
            [
                'slug' => 'sihanoukville',
                'name' => 'Sihanoukville',
                'description' => 'Coastal beaches, islands, and resort stays on the Gulf of Thailand.',
                'highlights' => ['Otres Beach', 'Koh Rong ferries', 'Sunset docks'],
                'hero_image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80',
                'seo_title' => 'Hotels in Sihanoukville | Greyon',
                'seo_description' => 'Book hotels in Sihanoukville and the Cambodian coast.',
            ],
            [
                'slug' => 'kampot',
                'name' => 'Kampot',
                'description' => 'River town calm — pepper farms, caves, and boutique riverside stays.',
                'highlights' => ['Kampot River', 'Pepper plantations', 'Bokor National Park'],
                'hero_image' => 'https://images.unsplash.com/photo-1540541338287-41700207dee6?auto=format&fit=crop&w=1200&q=80',
                'seo_title' => 'Hotels in Kampot | Greyon',
                'seo_description' => 'Book hotels in Kampot, Cambodia\'s riverside escape.',
            ],
        ];

        foreach ($rows as $row) {
            Location::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'highlights' => $row['highlights'],
                    'hero_image' => $row['hero_image'],
                    'status' => 'published',
                    'seo_title' => $row['seo_title'],
                    'seo_description' => $row['seo_description'],
                ]
            );
        }
    }
}
