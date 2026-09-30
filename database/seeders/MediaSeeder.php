<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\MediaItem;
use Illuminate\Database\Seeder;

/**
 * Site media library — Unsplash stills themed to Greyon destinations /
 * hotels. Each asset attaches to a destination; hotel-specific shots
 * also pin hotel_id for library filtering.
 */
class MediaSeeder extends Seeder
{
    public function run(): void
    {
        // No otres-bay/harbor-light/coral-inn media rows here (removed
        // 2026-09-29), and none for riverside/capitol-suites/mekong-house/
        // pepper-house/bokor-view/salt-field-inn either (removed
        // 2026-09-30, alongside HotelSeeder's whole dummy catalog -- see
        // its docblock): each fell back to a stale caption for a hotel
        // that no longer exists. The 2 generic Phnom Penh shots and 1
        // Sihanoukville shot below are location-level (no hotel tag), so
        // they're unaffected by either removal.
        $adminId = Admin::where('email', 'admin@greyon.com.kh')->value('id');

        $bySlug = fn (string $slug) => Location::where('slug', $slug)->value('id');
        $hotel = fn (string $slug) => Hotel::where('slug', $slug)->first();

        $pp = $bySlug('phnom-penh');
        $sv = $bySlug('sihanoukville');
        $kp = $bySlug('kampot');

        $items = [
            [
                'src' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Greyon suite · king bed and soft light',
                'location_id' => $pp,
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Greyon breakfast · riverside terrace',
                'location_id' => $pp,
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Greyon spa · calm indoor pool',
                'location_id' => $sv ?? $pp,
            ],
        ];

        foreach ($items as $row) {
            $locationId = $row['location_id'] ?? null;
            $hotelId = null;

            if (! empty($row['hotel'])) {
                $h = $hotel($row['hotel']);
                if ($h) {
                    $hotelId = $h->id;
                    $locationId = $h->location_id;
                }
            }

            // Fallbacks if seed order left locations empty.
            $locationId ??= $pp ?? $sv ?? $kp;

            MediaItem::updateOrCreate(
                ['src' => $row['src']],
                [
                    'alt' => $row['alt'],
                    'location_id' => $locationId,
                    'hotel_id' => $hotelId,
                    'created_by_admin_id' => $adminId,
                ]
            );
        }
    }
}
