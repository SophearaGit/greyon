<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Ids intentionally match AdminSeeder's placeholder `locationIds`
 * (1 = Phnom Penh, 2 = Siem Reap, 3 = Kampot, 4 = Sihanoukville) so
 * those placeholders become real FK references once this seeder runs
 * (AdminSeeder actually resolves by slug now, not by hard-coded id, but
 * the order still matters for anything that assumes it). Must run
 * before AdminSeeder. Kampot and Sihanoukville were added in Round 12
 * (2026-09-22) so the `Manager · Kampot` / `Manager · Sihanoukville`
 * packages have a real location to be scoped to — see PackageSeeder.
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

        Location::firstOrCreate(
            ['slug' => 'kampot'],
            [
                'name' => 'Kampot',
                'description' => 'Riverside charm, pepper farms, and a slower pace on the coast.',
                'highlights' => ['Kampot riverside', 'Pepper farms', 'Bokor Mountain'],
                'status' => 'published',
                'seo_title' => 'Hotels in Kampot | Greyon',
                'seo_description' => 'Book hotels in Kampot, Cambodia\'s riverside pepper town.',
            ]
        );

        Location::firstOrCreate(
            ['slug' => 'sihanoukville'],
            [
                'name' => 'Sihanoukville',
                'description' => 'Beach town gateway to Cambodia\'s southern islands.',
                'highlights' => ['Otres Beach', 'Island ferries', 'Ream National Park'],
                'status' => 'published',
                'seo_title' => 'Hotels in Sihanoukville | Greyon',
                'seo_description' => 'Book hotels in Sihanoukville and Cambodia\'s southern islands.',
            ]
        );
    }
}
