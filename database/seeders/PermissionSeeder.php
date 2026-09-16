<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * The permission catalog (2026-09-16, App\Models\Permission) — the doc
 * maker's "feature -> permissions" sample. Must run after
 * FeatureSeeder (needs the `locations` feature row to exist) and
 * before PackageSeeder (packages grant these by key via
 * `permissionKeys`). These 5 are the same set Round 8 seeded as child
 * *features* under `locations` (`features.parent_key`) before this
 * table existed — same keys/labels, just a different table now.
 * Developer can add more via
 * App\Http\Controllers\Developer\PermissionController.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $locations = Feature::where('key', 'locations')->firstOrFail();

        $locationPermissions = [
            ['locations_list', 'Edit destination content'],
            ['locations_managers', 'Assign managers per destination'],
            ['locations_hotels', 'Hotels under locations'],
            ['locations_publish', 'Publish / archive'],
            ['locations_seo', 'SEO fields'],
        ];

        foreach ($locationPermissions as $i => [$key, $label]) {
            Permission::firstOrCreate(
                ['key' => $key],
                ['label' => $label, 'feature_id' => $locations->id, 'sort_order' => $i]
            );
        }
    }
}
