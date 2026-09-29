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
        // 2026-09-29, alongside those Sihanoukville placeholder demo
        // hotels -- see HotelSeeder's docblock): each fell back to a
        // stale caption for a hotel that no longer exists.
        $adminId = Admin::where('email', 'admin@greyon.com.kh')->value('id');

        $bySlug = fn (string $slug) => Location::where('slug', $slug)->value('id');
        $hotel = fn (string $slug) => Hotel::where('slug', $slug)->first();

        $pp = $bySlug('phnom-penh');
        $sv = $bySlug('sihanoukville');
        $kp = $bySlug('kampot');

        $items = [
            [
                'src' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Riverside Hotel · Phnom Penh riverfront pool',
                'hotel' => 'riverside',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Capitol Suites · modern lobby Phnom Penh',
                'hotel' => 'capitol-suites',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Mekong House · boutique courtyard',
                'hotel' => 'mekong-house',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1540541338287-41700207dee6?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Pepper House · Kampot river deck',
                'hotel' => 'pepper-house',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Bokor View Lodge · mountain outlook',
                'hotel' => 'bokor-view',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Salt Field Inn · countryside morning',
                'hotel' => 'salt-field-inn',
            ],
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
