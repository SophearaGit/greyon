<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

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
 *
 * Real photos (2026-10-22): the picsum placeholders above were replaced
 * with free-licensed Wikimedia Commons photos committed under
 * database/seeders/assets/locations/<slug>/ (credits in CREDITS.md there),
 * copied to the public disk by SeedAssetsSeeder and referenced via
 * Storage::disk('public')->url() like the hotel seeders. images() is also
 * used by the greyon:sync-content command.
 */
class LocationSeeder extends Seeder
{
    public const GALLERY_COUNT = 3;

    /**
     * hero_image + gallery for a destination slug, as public-disk URLs.
     *
     * @return array{hero_image: string, gallery: list<string>}
     */
    public static function images(string $slug): array
    {
        $disk = Storage::disk('public');

        return [
            'hero_image' => $disk->url("locations/{$slug}/hero.jpg"),
            'gallery' => array_map(
                fn (int $i) => $disk->url("locations/{$slug}/gallery-{$i}.jpg"),
                range(1, self::GALLERY_COUNT)
            ),
        ];
    }

    public function run(): void
    {
        Location::updateOrCreate(
            ['slug' => 'phnom-penh'],
            [
                'name' => 'Phnom Penh',
                'description' => 'The capital — riverside hotels, city tours, and business stays.',
                'phone' => '+855 89 976 888',
                ...self::images('phnom-penh'),
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
                ...self::images('kampot'),
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
                ...self::images('sihanoukville'),
                'highlights' => ['Otres Beach', 'Island ferries', 'Ream National Park'],
                'status' => 'published',
                'seo_title' => 'Hotels in Sihanoukville | Greyon',
                'seo_description' => 'Book hotels in Sihanoukville and Cambodia\'s southern islands.',
            ]
        );
    }
}
