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
 *
 * `hero_image`/`gallery` (2026-09-22, Round 12.2): the public-facing
 * site (greyon.site — a separate frontend, not in this repo) showed a
 * broken image block on every location card. Root cause: this seeder
 * never set `hero_image`/`gallery` at all, so `LocationResource`'s
 * `heroImage`/`gallery` fields were `null`/`[]` for all 4 locations —
 * not a frontend bug, just missing seed data. Fixed with working
 * placeholder photos (picsum.photos/seed/<slug>, deterministic and
 * stable — always resolves regardless of any specific photo id) until
 * real photography is supplied; swap the URLs below for real ones via
 * the same fields whenever that's ready — no schema change needed
 * either way. Uses `updateOrCreate` (not `firstOrCreate`) specifically
 * so a plain `php artisan db:seed` reapplies these onto locations that
 * already exist in a real database — an admin's own edits afterward
 * (via `PATCH /admin/locations/:id`) still take precedence over the
 * *next* deploy only if this seeder isn't re-run against that row.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::updateOrCreate(
            ['slug' => 'phnom-penh'],
            [
                'name' => 'Phnom Penh',
                'description' => 'The capital — riverside hotels, city tours, and business stays.',
                'hero_image' => 'https://picsum.photos/seed/greyon-phnom-penh/1600/900',
                'gallery' => [
                    'https://picsum.photos/seed/greyon-phnom-penh-2/1600/900',
                    'https://picsum.photos/seed/greyon-phnom-penh-3/1600/900',
                ],
                'highlights' => ['Riverside promenade', 'Royal Palace', 'Central Market'],
                'status' => 'published',
                'seo_title' => 'Hotels in Phnom Penh | Greyon',
                'seo_description' => 'Book hotels in Phnom Penh, Cambodia\'s capital city.',
            ]
        );

        Location::updateOrCreate(
            ['slug' => 'siem-reap'],
            [
                'name' => 'Siem Reap',
                'description' => 'Gateway to Angkor Wat — resorts, boutique stays, and temple tours.',
                'hero_image' => 'https://picsum.photos/seed/greyon-siem-reap/1600/900',
                'gallery' => [
                    'https://picsum.photos/seed/greyon-siem-reap-2/1600/900',
                    'https://picsum.photos/seed/greyon-siem-reap-3/1600/900',
                ],
                'highlights' => ['Angkor Archaeological Park', 'Pub Street', 'Tonle Sap Lake'],
                'status' => 'published',
                'seo_title' => 'Hotels in Siem Reap | Greyon',
                'seo_description' => 'Book hotels in Siem Reap, near Angkor Wat.',
            ]
        );

        Location::updateOrCreate(
            ['slug' => 'kampot'],
            [
                'name' => 'Kampot',
                'description' => 'Riverside charm, pepper farms, and a slower pace on the coast.',
                'hero_image' => 'https://picsum.photos/seed/greyon-kampot/1600/900',
                'gallery' => [
                    'https://picsum.photos/seed/greyon-kampot-2/1600/900',
                    'https://picsum.photos/seed/greyon-kampot-3/1600/900',
                ],
                'highlights' => ['Kampot riverside', 'Pepper farms', 'Bokor Mountain'],
                'status' => 'published',
                'seo_title' => 'Hotels in Kampot | Greyon',
                'seo_description' => 'Book hotels in Kampot, Cambodia\'s riverside pepper town.',
            ]
        );

        Location::updateOrCreate(
            ['slug' => 'sihanoukville'],
            [
                'name' => 'Sihanoukville',
                'description' => 'Beach town gateway to Cambodia\'s southern islands.',
                'hero_image' => 'https://picsum.photos/seed/greyon-sihanoukville/1600/900',
                'gallery' => [
                    'https://picsum.photos/seed/greyon-sihanoukville-2/1600/900',
                    'https://picsum.photos/seed/greyon-sihanoukville-3/1600/900',
                ],
                'highlights' => ['Otres Beach', 'Island ferries', 'Ream National Park'],
                'status' => 'published',
                'seo_title' => 'Hotels in Sihanoukville | Greyon',
                'seo_description' => 'Book hotels in Sihanoukville and Cambodia\'s southern islands.',
            ]
        );
    }
}
