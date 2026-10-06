<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Location;
use App\Models\RatePlan;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Real client property — Greyon Hotel & Serviced Apartment, Sihanoukville
 * (2026-09-29). Sourced from the client-supplied "Greyon SHV Apartment /
 * Hotel Info" package. Kept as its own seeder, separate from
 * HotelSeeder/RoomTypeSeeder/RatePlanSeeder, which only hold generic
 * placeholder demo properties — this is real, go-live content for an
 * actual property, not a template to be duplicated per hotel.
 *
 * Fields the source doc didn't provide, and what was done instead:
 *   - lat/lng (2026-09-30): resolved from the client's Google Maps
 *     share link (below, now also stored in `map_embed_url`) —
 *     10.6253316, 103.5162819, confirmed against "Greyon Serviced
 *     Apartment Sihanouk Ville" on the map. The share link itself
 *     redirects to a full Maps URL carrying these same coordinates.
 *   - No stated per-room occupancy (max adults/children). Inferred from
 *     bedroom/bunk counts (documented inline below) — confirm with the
 *     client and adjust via the admin panel if these don't match intent.
 *   - No stated tax/service-fee percent, unlike the demo catalog's
 *     10%/5%. Left at 0/0 rather than inventing a charge on real prices
 *     — add one via `PATCH /admin/rate-plans/{id}` if the client
 *     actually charges tax/service on top of the nightly rate.
 *   - "Star rating" (3) and "Property Type" (Hotel & Serviced
 *     Apartment) now have their own columns (2026-09-30:
 *     `property_type`/`star_rating`, added below) — still also
 *     mentioned in prose inside `description` for now, harmless
 *     duplication, not worth rewriting that paragraph over.
 *   - Nearby landmarks (doc section 4) now have a home: a new
 *     `nearby_landmarks` JSON column on `hotels` (2026-09-30),
 *     `{ place, distance }` pairs, seeded below in the doc's own
 *     order.
 *   - Monthly/long-stay rates: doc says to just show "Contact us for
 *     more information" — that's frontend copy (this repo has no
 *     frontend), so it's left as a `policies` note here instead.
 *
 * Real photos (2026-10-06): the client supplied a render/photo package
 * for this property (folder `greyon-sihanoukville-imgs`), copied into
 * `storage/app/public/hotels/greyon-shv/<room-slug>/NN.jpg` the same
 * way as GreyonPhnomPenhSeeder — see `Storage::disk('public')->url()`
 * usage in `seedRoom()` below. Hotel-level `hero_image`/`gallery` now
 * use the real exterior/BBQ/sky-bar renders (`.../hotel/NN.jpg`)
 * instead of picsum placeholders.
 *
 * The supplied photo folders weren't labeled with room-type slugs, so
 * these mappings were confirmed with the client/project owner rather
 * than guessed outright, given the real pricing tied to each category:
 *   - "Sinble Bed" [sic] folder → the 1-Bedroom hotel rooms: codes
 *     `1BB01F`/`1BB01R` (Balcony, two sampled units) → 1br-balcony;
 *     `1BN01R1`/`1BN02R2` (no-balcony/Middle, two sampled units) →
 *     1br-middle.
 *   - "Double Beds" folder → the 2-Bedroom hotel rooms, same B/N
 *     coding: `2BB01F`/`2BB01R` → 2br-balcony; `2BN01R1`/`2BN02R2` →
 *     2br-middle.
 *   - "4- Dormitary" folder → the two dorm rooms: "Dormitory 01" (4
 *     photos, the wider-looking room with more bunk frames) →
 *     dorm-8-bunk; "Dormitory 02" (2 photos) → dorm-4-bunk-balcony.
 *     Confirmed with the client rather than inferred from bunk count
 *     alone — the render angles made an exact count unreliable.
 *   - No photos exist labeled for the 2-Bed Family Room (Middle/
 *     Balcony). Per client confirmation, these reuse the same photos
 *     as the plain 2-Bedroom Middle/Balcony rooms (2br-middle /
 *     2br-balcony) since they appear to be the same physical room,
 *     just sold under family occupancy/pricing — flagged here in case
 *     the client later supplies Family-Room-specific photos.
 *   - None of the 5 Serviced Apartment Suite types (Single/Double
 *     Bedroom, Middle/Balcony, and the 3-bed Balcony variant) had any
 *     photos in the supplied package — these keep the picsum
 *     placeholder pattern below until real photography is supplied.
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
                'property_type' => 'Hotel & Serviced Apartment',
                'star_rating' => 3,
                'short_description' => 'Conveniently located in the center of Sihanoukville on Ekareach Street. Free parking, free Wi-Fi, 24-hour security, daily housekeeping options, fitness facilities, and rooftop outdoor space. Close to beaches, markets, and public transport.',
                'description' => 'Greyon Hotel & Serviced Apartment is a 3-star hotel and serviced apartment property in the heart of Sihanoukville on Ekareach Street. Guests enjoy free parking, free Wi-Fi, 24-hour security, daily housekeeping options, fitness facilities, and rooftop outdoor space — close to beaches, markets, and public transport.',
                'address' => '#300, Ekareach Street, Phum 1, Sangkat No. 3, Preah Sihanouk City, Preah Sihanouk Province, Cambodia',
                'area' => 'Central / Ekareach Street',
                'lat' => 10.6253316,
                'lng' => 103.5162819,
                'map_embed_url' => 'https://maps.app.goo.gl/NEc4GV6VBNboUsVH7',
                'phone' => '+855 76 4938 886',
                'email' => 'info@greyon.com.kh',
                'hero_image' => Storage::disk('public')->url('hotels/greyon-shv/hotel/01.jpg'),
                'gallery' => collect(range(2, 9))
                    ->map(fn ($i) => Storage::disk('public')->url(sprintf('hotels/greyon-shv/hotel/%02d.jpg', $i)))
                    ->all(),
                'amenities' => [
                    'Free WiFi', 'Gym', "Kid's Playground", 'Billiard', 'Table Tennis',
                    'Parking Space', 'BBQ Kitchen', 'Rooftop Outdoor Space', '24/7 Security',
                ],
                'policies' => [
                    'Apartment services: linen changed once a week, cleaning twice a week.',
                    'Apartment utilities: electricity $0.30/kWh, water $0.70/m³.',
                    'Monthly and long-stay rates available on request — contact us for pricing.',
                ],
                'nearby_landmarks' => [
                    ['place' => 'China Wanda Supermarket', 'distance' => '50 m'],
                    ['place' => 'Doctor Chev Sam An', 'distance' => '50 m'],
                    ['place' => 'Sihanoukville Bus Terminal', 'distance' => '~160–930 m'],
                    ['place' => 'Phsar Leu Market', 'distance' => '1.5 km'],
                    ['place' => 'Golden Lion', 'distance' => '1.5–1.8 km'],
                    ['place' => 'Serendipity Beach', 'distance' => '2.2 km'],
                    ['place' => 'Independence Beach', 'distance' => '2.2 km'],
                    ['place' => 'Ochheuteal Beach', 'distance' => '3.5 km'],
                    ['place' => 'Sihanoukville International Airport (KOS)', 'distance' => '14.5–14.8 km'],
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
        // with the client. `photoFolder`/`photoCount`: real photos
        // (2026-10-06) — see class docblock for how each folder was
        // mapped. Family Room entries deliberately point at the same
        // folders as the plain 2-Bedroom rooms (no dedicated Family
        // Room photos exist yet).
        $hotelRooms = [
            ['slug' => 'dorm-4-bunk-balcony', 'name' => 'Dormitory — 4-Bunkbed (Balcony)', 'bed_type' => '4 bunk beds', 'qty' => 1, 'rate' => 80, 'balcony' => true, 'adults' => 4, 'children' => 0, 'guests' => 4, 'photoFolder' => 'dorm-4-bunk-balcony', 'photoCount' => 2],
            ['slug' => 'dorm-8-bunk', 'name' => 'Dormitory — 8-Bunkbed', 'bed_type' => '8 bunk beds', 'qty' => 1, 'rate' => 150, 'balcony' => false, 'adults' => 8, 'children' => 0, 'guests' => 8, 'photoFolder' => 'dorm-8-bunk', 'photoCount' => 4],
            ['slug' => '1br-middle', 'name' => '1-Bedroom — Middle Room', 'bed_type' => '1 bedroom', 'qty' => 15, 'rate' => 40, 'balcony' => false, 'adults' => 2, 'children' => 1, 'guests' => 3, 'photoFolder' => '1br-middle', 'photoCount' => 3],
            ['slug' => '1br-balcony', 'name' => '1-Bedroom — Balcony Room', 'bed_type' => '1 bedroom', 'qty' => 7, 'rate' => 45, 'balcony' => true, 'adults' => 2, 'children' => 1, 'guests' => 3, 'photoFolder' => '1br-balcony', 'photoCount' => 5],
            ['slug' => '2br-middle', 'name' => '2-Bedroom — Middle Room', 'bed_type' => '2 bedrooms', 'qty' => 8, 'rate' => 55, 'balcony' => false, 'adults' => 4, 'children' => 1, 'guests' => 5, 'photoFolder' => '2br-middle', 'photoCount' => 3],
            ['slug' => '2br-balcony', 'name' => '2-Bedroom — Balcony Room', 'bed_type' => '2 bedrooms', 'qty' => 4, 'rate' => 60, 'balcony' => true, 'adults' => 4, 'children' => 1, 'guests' => 5, 'photoFolder' => '2br-balcony', 'photoCount' => 6],
            ['slug' => '2br-family-middle', 'name' => '2-Bed Family Room — Middle Room', 'bed_type' => '2 bedrooms (family)', 'qty' => 8, 'rate' => 70, 'balcony' => false, 'adults' => 4, 'children' => 2, 'guests' => 6, 'photoFolder' => '2br-middle', 'photoCount' => 3],
            ['slug' => '2br-family-balcony', 'name' => '2-Bed Family Room — Balcony Room', 'bed_type' => '2 bedrooms (family)', 'qty' => 4, 'rate' => 75, 'balcony' => true, 'adults' => 4, 'children' => 2, 'guests' => 6, 'photoFolder' => '2br-balcony', 'photoCount' => 6],
        ];

        // Section C: Serviced Apartment Suites (nightly rates shown;
        // monthly/long-stay on request — see hotel `policies` above).
        // No real photos exist for these yet (see class docblock) —
        // still on the picsum placeholder pattern via seedRoom()'s
        // fallback.
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

        if (isset($room['photoFolder'], $room['photoCount'])) {
            $images = collect(range(1, $room['photoCount']))
                ->map(fn ($i) => Storage::disk('public')->url(
                    sprintf('hotels/greyon-shv/%s/%02d.jpg', $room['photoFolder'], $i)
                ))
                ->all();
        } else {
            $seed = 'greyon-shv-'.$room['slug'];
            $images = [
                "https://picsum.photos/seed/{$seed}/1200/800",
                "https://picsum.photos/seed/{$seed}-2/1200/800",
            ];
        }

        $roomType = RoomType::updateOrCreate(
            ['hotel_id' => $hotel->id, 'slug' => $room['slug']],
            [
                'name' => $room['name'],
                'description' => $room['name'],
                'images' => $images,
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
