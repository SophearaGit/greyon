<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

/**
 * The feature catalog, matching the handoff spec's section 3 appendix
 * tables. Developer can add more via
 * App\Http\Controllers\Developer\FeatureController. Module-level keys
 * only — the finer-grained `locations_*` catalog that used to live
 * here as child features (`parent_key`) moved to `PermissionSeeder`
 * (2026-09-16, `App\Models\Permission`); this seeder must run before
 * that one, since it seeds the `locations` feature row those
 * permissions attach to.
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $adminParents = [
            ['dashboard', 'Dashboard'],
            ['locations', 'Locations'],
            ['hotels', 'Hotels'],
            ['rooms', 'Room types'],
            ['rates', 'Rates & availability'],
            ['bookings', 'Bookings'],
            ['news', 'News'],
            ['media', 'Media library (add-on)'],
            ['enquiries', 'Enquiries'],
            ['settings', 'SEO / Settings'],
            ['users', 'Users'],
            ['features', 'Packages builder'],
        ];

        foreach ($adminParents as $i => [$key, $label]) {
            Feature::firstOrCreate(
                ['key' => $key],
                ['label' => $label, 'category' => 'admin', 'sort_order' => $i]
            );
        }

        $public = [
            ['booking_public', '/booking'],
            ['news_public', '/news'],
            ['contact_public', '/contact'],
            ['portfolios', 'Portfolio stubs'],
        ];

        foreach ($public as $i => [$key, $label]) {
            Feature::firstOrCreate(
                ['key' => $key],
                ['label' => $label, 'category' => 'public', 'sort_order' => $i]
            );
        }
    }
}
