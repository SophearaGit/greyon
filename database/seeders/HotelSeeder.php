<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Ids intentionally match AdminSeeder's placeholder `hotelIds`
 * (1 = Riverside, 2 = Angkor). Must run after LocationSeeder and
 * before AdminSeeder.
 */
class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $phnomPenh = Location::where('slug', 'phnom-penh')->firstOrFail();
        $siemReap = Location::where('slug', 'siem-reap')->firstOrFail();

        Hotel::firstOrCreate(
            ['slug' => 'riverside'],
            [
                'location_id' => $phnomPenh->id,
                'name' => 'Riverside Hotel',
                'short_description' => 'A comfortable stay on the Tonle Sap riverfront.',
                'description' => 'Riverside Hotel sits on the Phnom Penh riverfront, minutes from the Royal Palace.',
                'address' => 'Sisowath Quay, Phnom Penh, Cambodia',
                'lat' => 11.5625,
                'lng' => 104.9310,
                'phone' => '+855 23 555 0101',
                'email' => 'riverside@greyon.com.kh',
                'amenities' => ['Free WiFi', 'Pool', 'Restaurant', 'Airport shuttle'],
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'featured' => true,
                'status' => 'published',
                'seo_title' => 'Riverside Hotel, Phnom Penh | Greyon',
                'seo_description' => 'Book Riverside Hotel on the Phnom Penh riverfront.',
            ]
        );

        Hotel::firstOrCreate(
            ['slug' => 'angkor'],
            [
                'location_id' => $siemReap->id,
                'name' => 'Angkor Hotel',
                'short_description' => 'Boutique comfort minutes from Angkor Archaeological Park.',
                'description' => 'Angkor Hotel is a boutique property close to the Angkor Archaeological Park.',
                'address' => 'Charles de Gaulle Blvd, Siem Reap, Cambodia',
                'lat' => 13.3633,
                'lng' => 103.8564,
                'phone' => '+855 63 555 0202',
                'email' => 'angkor@greyon.com.kh',
                'amenities' => ['Free WiFi', 'Pool', 'Spa', 'Bicycle rental'],
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'featured' => true,
                'status' => 'published',
                'seo_title' => 'Angkor Hotel, Siem Reap | Greyon',
                'seo_description' => 'Book Angkor Hotel near Angkor Wat, Siem Reap.',
            ]
        );
    }
}
