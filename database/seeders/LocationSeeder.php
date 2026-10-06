<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Seeds the 3 live destinations: Phnom Penh, Kampot, Sihanoukville.
 * AdminSeeder resolves each manager's location by slug (not id), so
 * this must still run before AdminSeeder. Kampot and Sihanoukville were
 * added in Round 12 (2026-09-22) so the `Manager · Kampot` /
 * `Manager · Sihanoukville` packages have a real location to be scoped
 * to — see PackageSeeder.
 *
 * No `siem-reap` entry (removed 2026-09-30): never had any hotels,
 * room types, or rate plans -- placeholder destination only. The one
 * news article that referenced it (`angkor-golden-hour`) was removed
 * from NewsSeeder along with it.
 *
 * `phone`/`email` (2026-09-30, Phnom Penh's phone added 2026-10-06):
 * each destination's own "Destination Information" doc gives it its
 * own real contact info, separate from its hotel's own phone/email
 * (which the matching Greyon*Seeder already sets) — Sihanoukville's
 * doc gave both phone and email, Phnom Penh's ("Greyon Serviced
 * Apartment — Phnom Penh Branch" package, see GreyonPhnomPenhSeeder's
 * docblock) gave a phone only, no email. Kampot is still left null:
 * no real destination contact info exists for it yet, same "don't
 * seed placeholder data" reasoning HotelSeeder's docblock uses.
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
                'phone' => '+855 89 976 888',
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
                'phone' => '+855 76 4938 886',
                'email' => 'info@greyon.com.kh',
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
