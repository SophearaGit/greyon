<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Location;
use App\Models\RatePlan;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

/**
 * Real client property — Greyon Hotel & Serviced Apartment, Sihanoukville
 * (2026-09-29). Sourced from the client-supplied "Greyon SHV Apartment /
 * Hotel Info" package. Kept as its own seeder, separate from
 * HotelSeeder/RoomTypeSeeder/RatePlanSeeder, which only hold generic
 * placeholder demo properties — this is real, go-live content for an
 * actual property, not a template to be duplicated per hotel.
 *
 * Fields the source doc didn't provide, and what was done instead:
 *   - No lat/lng (only a Google Maps share link, not raw coordinates —
 *     attempted to resolve it but the fetch was rate-limited). Left
 *     null; fill in via the admin panel once available (geocode the
 *     address or drop a pin from the Maps link below).
 *   - No photos at all. Uses the same deterministic placeholder-photo
 *     pattern as HotelSeeder (picsum.photos/unsplash) — swap for real
 *     photography via `PATCH /admin/hotels/{id}` whenever supplied.
 *   - No stated per-room occupancy (max adults/children). Inferred from
 *     bedroom/bunk counts (documented inline below) — confirm with the
 *     client and adjust via the admin panel if these don't match intent.
 *   - No stated tax/service-fee percent, unlike the demo catalog's
 *     10%/5%. Left at 0/0 rather than inventing a charge on real prices
 *     — add one via `PATCH /admin/rate-plans/{id}` if the client
 *     actually charges tax/service on top of the nightly rate.
 *   - "Star rating" (3-star) and "Property Type" (Hotel & Serviced
 *     Apartment) have no dedicated columns — folded into `description`.
 *   - Nearby landmarks (doc section 4) have no home in this schema at
 *     all — not seeded anywhere; flagged to the user separately.
 *   - Monthly/long-stay rates: doc says to just show "Contact us for
 *     more information" — that's frontend copy (this repo has no
 *     frontend), so it's left as a `policies` note here instead.
 */
class GreyonShvSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::where('slug', 'sihanoukville')->firstOrFail();

        $hotel = Hotel::updateOrCreate(
            ['slug' => 'greyon-shv'],
            [
                'location_id' => $location->id,
                'name' => 'Greyon Hotel & Serviced Apartment',
                'short_description' => 'Conveniently located in the center of Sihanoukville on Ekareach Street. Free parking, free Wi-Fi, 24-hour security, daily housekeeping options, fitness facilities, and rooftop outdoor space. Close to beaches, markets, and public transport.',
                'description' => 'Greyon Hotel & Serviced Apartment is a 3-star hotel and serviced apartment property in the heart of Sihanoukville on Ekareach Street. Guests enjoy free parking, free Wi-Fi, 24-hour security, daily housekeeping options, fitness facilities, and rooftop outdoor space — close to beaches, markets, and public transport.',
                'address' => '#300, Ekareach Street, Phum 1, Sangkat No. 3, Preah Sihanouk City, Preah Sihanouk Province, Cambodia',
                'lat' => null,
                'lng' => null,
                'phone' => '+855 76 4938 886',
                'email' => 'info@greyon.com.kh',
                'hero_image' => 'https://picsum.photos/seed/greyon-shv/1600/900',
                'gallery' => [
                    'https://picsum.photos/seed/greyon-shv-2/1600/900',
                    'https://picsum.photos/seed/greyon-shv-3/1600/900',
                    'https://picsum.photos/seed/greyon-shv-4/1600/900',
                ],
                'amenities' => [
                    'Free WiFi', 'Gym', "Kid's Playground", 'Billiard', 'Table Tennis',
                    'Parking Space', 'BBQ Kitchen', 'Rooftop Outdoor Space', '24/7 Security',
                ],
                'policies' => [
                    'Apartment services: linen changed once a week, cleaning twice a week.',
                    'Apartment utilities: electricity $0.30/kWh, water $0.70/m³.',
                    'Monthly and long-stay rates available on request — contact us for pricing.',
                ],
                'featured' => true,
                'status' => 'published',
                'seo_title' => 'Greyon Hotel & Serviced Apartment, Sihanoukville | Greyon',
                'seo_description' => 'Book Greyon Hotel & Serviced Apartment on Ekareach Street, Sihanoukville — hotel rooms and fully furnished apartment suites.',
            ]
        );

        // Room-level amenities: the doc's "Room Includes" apply to every
        // category; "Fully Furnished Apartment Extra Includes" apply
        // only to the Serviced Apartment Suites (section C below).
        $baseAmenities = [
            'Air conditioning', 'Private bathroom (hot & cold shower)', 'Bed linen & towels',
            'TV', 'Free toiletries', 'Safe box', 'Water heater',
        ];
        $apartmentExtraAmenities = ['Living room', 'Kitchen & mini bar', 'Dining area', 'Laundry area'];

        // Section B: Hotel Rooms (nightly rates). Occupancy inferred from
        // bunk/bedroom counts — not stated in the source doc, confirm
        // with the client.
        $hotelRooms = [
            ['slug' => 'dorm-4-bunk-balcony', 'name' => 'Dormitory — 4-Bunkbed (Balcony)', 'bed_type' => '4 bunk beds', 'qty' => 1, 'rate' => 80, 'balcony' => true, 'adults' => 4, 'children' => 0, 'guests' => 4],
            ['slug' => 'dorm-8-bunk', 'name' => 'Dormitory — 8-Bunkbed', 'bed_type' => '8 bunk beds', 'qty' => 1, 'rate' => 150, 'balcony' => false, 'adults' => 8, 'children' => 0, 'guests' => 8],
            ['slug' => '1br-middle', 'name' => '1-Bedroom — Middle Room', 'bed_type' => '1 bedroom', 'qty' => 15, 'rate' => 40, 'balcony' => false, 'adults' => 2, 'children' => 1, 'guests' => 3],
            ['slug' => '1br-balcony', 'name' => '1-Bedroom — Balcony Room', 'bed_type' => '1 bedroom', 'qty' => 7, 'rate' => 45, 'balcony' => true, 'adults' => 2, 'children' => 1, 'guests' => 3],
            ['slug' => '2br-middle', 'name' => '2-Bedroom — Middle Room', 'bed_type' => '2 bedrooms', 'qty' => 8, 'rate' => 55, 'balcony' => false, 'adults' => 4, 'children' => 1, 'guests' => 5],
            ['slug' => '2br-balcony', 'name' => '2-Bedroom — Balcony Room', 'bed_type' => '2 bedrooms', 'qty' => 4, 'rate' => 60, 'balcony' => true, 'adults' => 4, 'children' => 1, 'guests' => 5],
            ['slug' => '2br-family-middle', 'name' => '2-Bed Family Room — Middle Room', 'bed_type' => '2 bedrooms (family)', 'qty' => 8, 'rate' => 70, 'balcony' => false, 'adults' => 4, 'children' => 2, 'guests' => 6],
            ['slug' => '2br-family-balcony', 'name' => '2-Bed Family Room — Balcony Room', 'bed_type' => '2 bedrooms (family)', 'qty' => 4, 'rate' => 75, 'balcony' => true, 'adults' => 4, 'children' => 2, 'guests' => 6],
        ];

        // Section C: Serviced Apartment Suites (nightly rates shown;
        // monthly/long-stay on request — see hotel `policies` above).
        $apartmentSuites = [
            ['slug' => 'suite-single-45-middle', 'name' => 'Serviced Apartment — Single Bedroom (Middle, 45 sqm)', 'bed_type' => '1 bedroom', 'size' => '45 sqm', 'qty' => 8, 'rate' => 80, 'balcony' => false, 'adults' => 2, 'children' => 1, 'guests' => 3],
            ['slug' => 'suite-single-50-balcony', 'name' => 'Serviced Apartment — Single Bedroom (Balcony, 50 sqm)', 'bed_type' => '1 bedroom', 'size' => '50 sqm', 'qty' => 8, 'rate' => 85, 'balcony' => true, 'adults' => 2, 'children' => 1, 'guests' => 3],
            ['slug' => 'suite-double-2bed-75-middle', 'name' => 'Serviced Apartment — Double Bedroom, 2 Beds (Middle, 75 sqm)', 'bed_type' => '2 beds', 'size' => '75 sqm', 'qty' => 4, 'rate' => 100, 'balcony' => false, 'adults' => 4, 'children' => 1, 'guests' => 5],
            ['slug' => 'suite-double-2bed-80-balcony', 'name' => 'Serviced Apartment — Double Bedroom, 2 Beds (Balcony, 80 sqm)', 'bed_type' => '2 beds', 'size' => '80 sqm', 'qty' => 4, 'rate' => 110, 'balcony' => true, 'adults' => 4, 'children' => 1, 'guests' => 5],
            ['slug' => 'suite-double-3bed-95-balcony', 'name' => 'Serviced Apartment — Double Bedroom, 3 Beds (Balcony, 95 sqm)', 'bed_type' => '3 beds', 'size' => '95 sqm', 'qty' => 4, 'rate' => 120, 'balcony' => true, 'adults' => 6, 'children' => 2, 'guests' => 8],
        ];

        foreach ($hotelRooms as $room) {
            $this->seedRoom($hotel, $room, $baseAmenities, null);
        }

        foreach ($apartmentSuites as $room) {
            $this->seedRoom($hotel, $room, [...$baseAmenities, ...$apartmentExtraAmenities], $room['size']);
        }
    }

    /**
     * @param  array<string, mixed>  $room
     * @param  list<string>  $amenities
     */
    private function seedRoom(Hotel $hotel, array $room, array $amenities, ?string $roomSize): void
    {
        if ($room['balcony']) {
            $amenities[] = 'Private balcony';
        }

        $roomType = RoomType::updateOrCreate(
            ['hotel_id' => $hotel->id, 'slug' => $room['slug']],
            [
                'name' => $room['name'],
                'description' => $room['name'],
                'images' => null,
                'bed_type' => $room['bed_type'],
                'room_size' => $roomSize,
                'max_adults' => $room['adults'],
                'max_children' => $room['children'],
                'max_guests' => $room['guests'],
                'amenities' => $amenities,
                'base_inventory' => $room['qty'],
                'status' => 'published',
            ]
        );

        RatePlan::updateOrCreate(
            ['room_type_id' => $roomType->id, 'name' => 'Nightly Rate'],
            [
                'description' => 'Standard nightly rate. Monthly / long-stay rates available on request.',
                'meal_benefit' => null,
                'cancellation_policy' => null,
                'base_price' => $room['rate'],
                'tax_percent' => 0,
                'service_fee_percent' => 0,
                'status' => 'published',
            ]
        );
    }
}
