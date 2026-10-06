<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Location;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Real client property — Greyon Serviced Apartment, Phnom Penh
 * (2026-10-06, photos added 2026-10-06). Sourced from the
 * client-supplied "Greyon Serviced Apartment — Phnom Penh Branch" info
 * package (October 2026) plus a separate real-photo folder ("Greyon
 * TTP") delivered the same day. Kept as its own seeder, same pattern
 * as GreyonShvSeeder, separate from HotelSeeder/RoomTypeSeeder/
 * RatePlanSeeder (generic placeholder demo catalog only, currently
 * empty — see that seeder's docblock).
 *
 * Unlike the Sihanoukville doc, this one is missing data that the
 * schema/flow actually needs. Rather than invent numbers for real
 * client content, these are left out and flagged here instead:
 *
 *   - **No rates at all.** Section 3B's room table is headed "Monthly
 *     rates shown below" but the table itself only has
 *     Category/Size/Bed-Bath/Key Features columns — no price in any
 *     unit. `rate_plans.base_price` is NOT NULL, so no RatePlan rows
 *     are created for any of these room types. Add real RatePlans via
 *     `POST /admin/rate-plans` once the client provides actual
 *     nightly or monthly pricing — the doc says to contact the
 *     property directly for rates in the meantime.
 *   - **No unit count per category.** The SHV doc gave a quantity per
 *     room type; this doc doesn't, so `base_inventory` is left at its
 *     column default (0) rather than guessed. Set a real count via
 *     the admin panel once known.
 *   - Because of the two gaps above, this hotel and its room types are
 *     seeded as `status => 'draft'` (not `published`) — there's
 *     nothing bookable to show yet. Flip to `published` once pricing
 *     and inventory are in and the client confirms it's ready to go
 *     live.
 *   - No lat/lng or map link supplied (unlike SHV, which had a Google
 *     Maps share link) — left null rather than guessed from the
 *     address.
 *   - No email given (doc only has a phone number) — left null, same
 *     "don't seed placeholder contact info" reasoning LocationSeeder
 *     uses.
 *   - `featured`: not stated anywhere in the doc, defaulted to
 *     `false` as a judgment call (GreyonShvSeeder's SHV hotel is
 *     `true`) — flip via `PATCH /admin/hotels/{id}` if the client
 *     wants Phnom Penh featured too.
 *   - Occupancy (`max_adults`/`max_children`/`max_guests`): not stated
 *     either, inferred from bedroom count using the same mapping
 *     GreyonShvSeeder used for SHV's apartment suites (1BR → 2 adults
 *     + 1 child, 2BR → 4 adults + 1 child) — confirm with the client
 *     and adjust via the admin panel if these don't match intent.
 *   - Bathroom count (1 BA / 2 BA per the doc's "Bed/Bath" column) has
 *     no dedicated column on `room_types` (only `bed_type`/
 *     `room_size` free-text fields exist) — folded into each room
 *     type's `name`/`description` instead rather than adding a new
 *     migration for a single extra fact.
 *
 * **Photos (2026-10-06).** The client's photo folder ("Greyon TTP")
 * only covers the 2-bedroom unit — and turned out to contain two
 * visibly different real apartments ("2 Bedroom Layout A", 10 photos;
 * "2 Bedroom Layout B", 15 photos — different furniture/decor
 * throughout, not just different angles of one unit), even though the
 * source doc's table lists only a single undifferentiated
 * "2-Bedroom | 80 sqm" row. Per the user's explicit choice, this is
 * modeled as two separate room types (`2br-80sqm-layout-a` /
 * `-layout-b`) rather than one merged gallery, since they're genuinely
 * two different physical apartments and may end up with different
 * rates/inventory once pricing is available. The old single
 * `2br-80sqm` slug (seeded 2026-10-06, before the photos arrived) is
 * explicitly deleted below — safe, since no RatePlan was ever created
 * against it (see the no-rates gap above).
 *
 * Images themselves are copied into `storage/app/public/hotels/
 * greyon-pp/<room-slug>/` (served via the `public` disk / `php artisan
 * storage:link`, same mechanism the app already exposes — see
 * `App\Http\Controllers\Admin\MediaItemController`'s docblock, "URL-
 * based library") and referenced here via `Storage::disk('public')
 * ->url(...)` rather than a hardcoded host, so this works the same in
 * any environment. The three 1-bedroom categories have **no** photos
 * in the client's folder at all — they keep the picsum placeholder
 * pattern below until real ones are supplied. The folder also
 * included a logo SVG ("Greyon Logo only-bigger weight.svg") — not
 * used here, there's no logo field on `hotels` yet.
 */
class GreyonPhnomPenhSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::where('slug', 'phnom-penh')->firstOrFail();

        $hotel = Hotel::updateOrCreate(
            ['slug' => 'greyon-pp'],
            [
                'location_id' => $location->id,
                'name' => 'Greyon Serviced Apartment',
                'property_type' => 'Serviced Apartment',
                'star_rating' => 3,
                'short_description' => 'Conveniently located in Chamkarmon, Phnom Penh. Fully furnished serviced apartments with free parking, free Wi-Fi, 24-hour security, gym, rooftop space, and weekly housekeeping. Ideal for both short and long stays in the capital.',
                'description' => 'Greyon Serviced Apartment is a 3-star serviced apartment property in Chamkarmon, Phnom Penh. Guests enjoy free parking, free Wi-Fi, 24-hour security, a gym, rooftop outdoor space, and weekly housekeeping — fully furnished units suited to both short and long stays in the capital.',
                'address' => '#400, Street 450, Toul Tom Poung 2 (TTP2), Chamkarmon, Phnom Penh, Cambodia',
                'area' => 'Chamkarmon (Chamkar Mon)',
                'lat' => null,
                'lng' => null,
                'map_embed_url' => null,
                'phone' => '+855 89 976 888',
                'email' => null,
                // Real photos (from the Layout B unit's kitchen/dining —
                // the best single establishing shot of the property).
                'hero_image' => Storage::disk('public')->url('hotels/greyon-pp/2br-80sqm-layout-b/15.jpg'),
                'gallery' => [
                    Storage::disk('public')->url('hotels/greyon-pp/2br-80sqm-layout-a/01.jpg'),
                    Storage::disk('public')->url('hotels/greyon-pp/2br-80sqm-layout-a/05.jpg'),
                    Storage::disk('public')->url('hotels/greyon-pp/2br-80sqm-layout-b/02.jpg'),
                ],
                'amenities' => [
                    'Free WiFi', 'Gym / Fitness Center', 'Rooftop Outdoor Space',
                    'Free Parking Space', '24/7 Security', 'Fire Protection System', 'Elevator',
                ],
                'policies' => [
                    'Apartment services: cleaning twice per week, bedsheet change once per week.',
                    'Utilities not included: electricity $0.25/kWh, water $0.30/m³.',
                    'Monthly rates and current availability available on request — contact the property.',
                ],
                'nearby_landmarks' => [
                    ['place' => 'Russian Market', 'distance' => '440 m'],
                    ['place' => 'Central Market', 'distance' => '790 m'],
                    ['place' => 'Tuol Sleng Genocide Museum', 'distance' => '1.3 km'],
                    ['place' => 'Independence Monument', 'distance' => '2.6 km'],
                    ['place' => 'Royal Palace / Silver Pagoda', 'distance' => '3.5 km'],
                    ['place' => 'Sisowath Quay', 'distance' => '4.0 km'],
                    ['place' => 'Wat Phnom', 'distance' => '4.2 km'],
                    ['place' => 'Choeung Ek Genocidal Center', 'distance' => '6.3 km'],
                    ['place' => 'Techo International Airport (KTI)', 'distance' => '20.2 km'],
                ],
                'featured' => false,
                'status' => 'draft',
                'seo_title' => 'Greyon Serviced Apartment, Phnom Penh | Greyon',
                'seo_description' => 'Fully furnished serviced apartments in Chamkarmon, Phnom Penh — free parking, free Wi-Fi, gym, and rooftop space. Ideal for short and long stays.',
            ]
        );

        // Superseded by the two layout-specific room types below, now
        // that real photos revealed two distinct 2-bedroom units — see
        // class docblock. Safe to delete: no RatePlan was ever created
        // against it.
        RoomType::where('hotel_id', $hotel->id)->where('slug', '2br-80sqm')->delete();

        // Section 3A: common to every unit — all units here are fully
        // furnished serviced apartments (section 3B), so the "Room
        // Includes" and "Fully Furnished Apartment Includes" lists
        // both apply to every category below, unlike GreyonShvSeeder
        // where only the apartment suites got the apartment extras.
        $amenities = [
            'Air Conditioning', 'Private Bathroom (Hot & Cold Shower)', 'Bed Linen & Towels',
            'TV', 'Free Toiletries', 'Water Heater',
            'Full Kitchen (with kitchenware set)', 'Dining Table', 'Private Balcony', 'Living Area',
        ];

        // Section 3B: Available Apartment Types. No rate or unit-count
        // column in the source table — see class docblock. The 1BR
        // sizes have no real photos (none supplied), so they keep the
        // picsum placeholder pattern; the 2BR categories are handled
        // separately below since they're photographed as two distinct
        // real units, not one.
        $roomCategories = [
            ['slug' => '1br-46-8sqm', 'label' => '1-Bedroom Apartment (46.80 sqm)', 'size' => '46.80 sqm', 'bed_type' => '1 bedroom', 'baths' => 1, 'adults' => 2, 'children' => 1, 'guests' => 3],
            ['slug' => '1br-48-4sqm', 'label' => '1-Bedroom Apartment (48.40 sqm)', 'size' => '48.40 sqm', 'bed_type' => '1 bedroom', 'baths' => 1, 'adults' => 2, 'children' => 1, 'guests' => 3],
            ['slug' => '1br-52-5sqm', 'label' => '1-Bedroom Apartment (52.50 sqm)', 'size' => '52.50 sqm', 'bed_type' => '1 bedroom', 'baths' => 1, 'adults' => 2, 'children' => 1, 'guests' => 3],
        ];

        foreach ($roomCategories as $room) {
            $seed = 'greyon-pp-'.$room['slug'];
            $description = "{$room['label']} — {$room['bed_type']} / {$room['baths']} bathroom(s), {$room['size']}. Fully furnished with balcony and full kitchen.";

            RoomType::updateOrCreate(
                ['hotel_id' => $hotel->id, 'slug' => $room['slug']],
                [
                    'name' => $room['label'],
                    'description' => $description,
                    'images' => [
                        "https://picsum.photos/seed/{$seed}/1200/800",
                        "https://picsum.photos/seed/{$seed}-2/1200/800",
                    ],
                    'bed_type' => $room['bed_type'],
                    'room_size' => $room['size'],
                    'max_adults' => $room['adults'],
                    'max_children' => $room['children'],
                    'max_guests' => $room['guests'],
                    'amenities' => $amenities,
                    'status' => 'draft',
                ]
            );
        }

        // 2-Bedroom — two real, visually distinct units (see class
        // docblock). Same size/bed-bath/occupancy per the source doc
        // (it never broke the 80 sqm row out by layout), differing
        // only in slug/name/photos.
        $twoBedroomLayouts = [
            ['slug' => '2br-80sqm-layout-a', 'label' => '2-Bedroom Apartment — Layout A (80 sqm)', 'folder' => '2br-80sqm-layout-a', 'count' => 10],
            ['slug' => '2br-80sqm-layout-b', 'label' => '2-Bedroom Apartment — Layout B (80 sqm)', 'folder' => '2br-80sqm-layout-b', 'count' => 15],
        ];

        foreach ($twoBedroomLayouts as $layout) {
            $images = [];
            for ($i = 1; $i <= $layout['count']; $i++) {
                $images[] = Storage::disk('public')->url(
                    sprintf('hotels/greyon-pp/%s/%02d.jpg', $layout['folder'], $i)
                );
            }

            RoomType::updateOrCreate(
                ['hotel_id' => $hotel->id, 'slug' => $layout['slug']],
                [
                    'name' => $layout['label'],
                    'description' => "{$layout['label']} — 2 bedrooms / 2 bathroom(s), 80 sqm. Fully furnished with balcony and full kitchen.",
                    'images' => $images,
                    'bed_type' => '2 bedrooms',
                    'room_size' => '80 sqm',
                    'max_adults' => 4,
                    'max_children' => 1,
                    'max_guests' => 5,
                    'amenities' => $amenities,
                    'status' => 'draft',
                ]
            );
        }
    }
}
