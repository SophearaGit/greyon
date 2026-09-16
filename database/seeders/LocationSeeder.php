<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Ids intentionally match AdminSeeder's placeholder `locationIds`
 * (1 = Phnom Penh, 2 = Siem Reap) so those placeholders become real FK
 * references once this seeder runs. Must run before AdminSeeder.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::firstOrCreate(
            ['slug' => 'phnom-penh'],
            [
                'name' => 'Phnom Penh',
                'description' => 'The capital — riverside hotels, city tours, and business stays.',
                'highlights' => ['Riverside promenade', 'Royal Palace', 'Central Market'],
                'status' => 'published',
                'seo_title' => 'Hotels in Phnom Penh | Greyon',
                'seo_description' => 'Book hotels in Phnom Penh, Cambodia\'s capital city.',
            ]
        );

        Location::firstOrCreate(
            ['slug' => 'siem-reap'],
            [
                'name' => 'Siem Reap',
                'description' => 'Gateway to Angkor Wat — resorts, boutique stays, and temple tours.',
                'highlights' => ['Angkor Archaeological Park', 'Pub Street', 'Tonle Sap Lake'],
                'status' => 'published',
                'seo_title' => 'Hotels in Siem Reap | Greyon',
                'seo_description' => 'Book hotels in Siem Reap, near Angkor Wat.',
            ]
        );
    }
}
