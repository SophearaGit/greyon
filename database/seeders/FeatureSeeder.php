<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

/**
 * Feature catalog matching greyon SPA `src/data/seed-features.ts`
 * (parent modules only). Location sub-keys live in PermissionSeeder.
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $adminParents = [
            ['dashboard', 'Dashboard', 'Admin home / ops overview'],
            ['locations', 'Locations', 'Destination CMS — parent module for location permissions'],
            ['hotels', 'Hotels', 'Hotel CMS (linked to locations via locationId)'],
            ['rooms', 'Room types', 'Rooms per hotel'],
            ['rates', 'Rates & availability', 'Inventory and nightly pricing'],
            ['bookings', 'Bookings', 'Reservation inbox / management'],
            ['news', 'News', 'News / blog CMS'],
            ['media', 'Media library', 'Optional asset library add-on'],
            ['enquiries', 'Enquiries', 'Contact form inbox'],
            ['settings', 'SEO / Settings', 'Site defaults and SEO'],
            ['users', 'People', 'Create managers & hotel desks; assign seats (roles) with location/hotel scope'],
            ['features', 'Seat types', 'Developer: build seat types (packages) from roles → features → permissions'],
        ];

        foreach ($adminParents as $i => [$key, $label, $description]) {
            Feature::updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'description' => $description,
                    'category' => 'admin',
                    'sort_order' => $i,
                ]
            );
        }

        $public = [
            ['booking_public', 'Public booking engine', 'Guest-facing /booking flow'],
            ['news_public', 'Public news', 'Guest-facing news pages'],
            ['contact_public', 'Public contact form', 'Guest-facing /contact'],
            ['portfolios', 'Portfolio stubs', 'Coming-soon portfolio pages'],
        ];

        foreach ($public as $i => [$key, $label, $description]) {
            Feature::updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'description' => $description,
                    'category' => 'public',
                    'sort_order' => $i,
                ]
            );
        }
    }
}
