<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Up to 3 hotels per location (matches package limit hotels_per_location).
 *
 * No `sihanoukville` entry (removed 2026-09-29): a real client property
 * (Greyon Hotel & Serviced Apartment, see GreyonShvSeeder) now occupies
 * that destination, and `hotels_per_location` counts every hotel in a
 * location regardless of who created it (Admin\HotelController's
 * docblock) — keeping 3 placeholder demo hotels here alongside it would
 * leave Sihanoukville permanently over its own cap. The 3 that used to
 * seed here (Otres Bay Resort, Harbor Light Hotel, Coral Inn) were
 * removed from both this seeder and the live database on the same day.
 */
class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'phnom-penh' => [
                [
                    'slug' => 'riverside',
                    'name' => 'Riverside Hotel',
                    'short_description' => 'A comfortable stay on the Tonle Sap riverfront.',
                    'description' => 'Riverside Hotel sits on the Phnom Penh riverfront, minutes from the Royal Palace.',
                    'address' => 'Sisowath Quay, Phnom Penh, Cambodia',
                    'lat' => 11.5625,
                    'lng' => 104.9310,
                    'phone' => '+855 23 555 0101',
                    'email' => 'riverside@greyon.com.kh',
                    'amenities' => ['Free WiFi', 'Pool', 'Restaurant', 'Airport shuttle'],
                    'featured' => true,
                    'hero_image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
                        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1400&q=80',
                    ],
                ],
                [
                    'slug' => 'capitol-suites',
                    'name' => 'Capitol Suites',
                    'short_description' => 'Modern suites near BKK1 for business travelers.',
                    'description' => 'Capitol Suites offers spacious rooms close to embassies and cafés.',
                    'address' => 'Street 308, Phnom Penh, Cambodia',
                    'lat' => 11.5508,
                    'lng' => 104.9210,
                    'phone' => '+855 23 555 0102',
                    'email' => 'capitol@greyon.com.kh',
                    'amenities' => ['Free WiFi', 'Gym', 'Coworking lounge'],
                    'featured' => false,
                    'hero_image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1400&q=80',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
                    ],
                ],
                [
                    'slug' => 'mekong-house',
                    'name' => 'Mekong House',
                    'short_description' => 'Boutique riverside boutique with calm courtyards.',
                    'description' => 'Mekong House is a quiet boutique stay overlooking the Mekong.',
                    'address' => 'National Road 6A, Phnom Penh, Cambodia',
                    'lat' => 11.5890,
                    'lng' => 104.9435,
                    'phone' => '+855 23 555 0103',
                    'email' => 'mekong@greyon.com.kh',
                    'amenities' => ['Free WiFi', 'Garden', 'Breakfast'],
                    'featured' => false,
                    'hero_image' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1400&q=80',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
                    ],
                ],
            ],
            'kampot' => [
                [
                    'slug' => 'pepper-house',
                    'name' => 'Pepper House',
                    'short_description' => 'Riverside boutique near Kampot\'s pepper farms.',
                    'description' => 'Pepper House blends river breezes with farm-to-table dining.',
                    'address' => 'River Road, Kampot, Cambodia',
                    'lat' => 10.6105,
                    'lng' => 104.1810,
                    'phone' => '+855 33 555 0301',
                    'email' => 'pepper@greyon.com.kh',
                    'amenities' => ['Free WiFi', 'River deck', 'Bicycle rental'],
                    'featured' => true,
                    'hero_image' => 'https://images.unsplash.com/photo-1540541338287-41700207dee6?auto=format&fit=crop&w=1400&q=80',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
                    ],
                ],
                [
                    'slug' => 'bokor-view',
                    'name' => 'Bokor View Lodge',
                    'short_description' => 'Quiet lodge with Bokor mountain outlooks.',
                    'description' => 'Bokor View Lodge is a calm base for day trips to the national park.',
                    'address' => 'East Bank, Kampot, Cambodia',
                    'lat' => 10.6200,
                    'lng' => 104.1900,
                    'phone' => '+855 33 555 0302',
                    'email' => 'bokor@greyon.com.kh',
                    'amenities' => ['Free WiFi', 'Garden', 'Breakfast'],
                    'featured' => false,
                    'hero_image' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=1400&q=80',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
                    ],
                ],
                [
                    'slug' => 'salt-field-inn',
                    'name' => 'Salt Field Inn',
                    'short_description' => 'Small inn near Kampot\'s salt fields.',
                    'description' => 'Salt Field Inn is simple, friendly, and close to countryside rides.',
                    'address' => 'Teuk Chou Road, Kampot, Cambodia',
                    'lat' => 10.5950,
                    'lng' => 104.1700,
                    'phone' => '+855 33 555 0303',
                    'email' => 'salt@greyon.com.kh',
                    'amenities' => ['Free WiFi', 'Cafe', 'Tours desk'],
                    'featured' => false,
                    'hero_image' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1540541338287-41700207dee6?auto=format&fit=crop&w=1400&q=80',
                    ],
                ],
            ],
        ];

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
