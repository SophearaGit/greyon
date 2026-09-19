<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Every feature gets list/create/update/delete permissions (CRUD).
 * Locations also keep richer SPA keys (managers, hotels, publish, seo).
 */
class PermissionSeeder extends Seeder
{
    /** @var list<array{0: string, 1: string, 2: string}> */
    private const CRUD = [
        ['list', 'List / view', 'See records in this module'],
        ['create', 'Create', 'Add new records'],
        ['update', 'Update', 'Edit existing records'],
        ['delete', 'Delete', 'Remove records'],
    ];

    public function run(): void
    {
        $features = Feature::query()->orderBy('sort_order')->orderBy('key')->get();

        foreach ($features as $feature) {
            $sort = 0;
            foreach (self::CRUD as [$action, $label, $description]) {
                Permission::updateOrCreate(
                    ['key' => "{$feature->key}_{$action}"],
                    [
                        'label' => "{$feature->label}: {$label}",
                        'description' => $description,
                        'feature_id' => $feature->id,
                        'sort_order' => $sort++,
                    ]
                );
            }
        }

        // Richer location capabilities used by the SPA / AccessService.
        $locations = Feature::where('key', 'locations')->first();
        if ($locations) {
            $extras = [
                ['locations_managers', 'Location managers', 'Assign managers to destinations', 10],
                ['locations_hotels', 'Hotels under locations', 'Link hotels to destinations', 11],
                ['locations_publish', 'Publish destinations', 'Publish / archive location pages', 12],
                ['locations_seo', 'Location SEO', 'Per-destination SEO fields', 13],
            ];
            foreach ($extras as [$key, $label, $description, $sort]) {
                Permission::updateOrCreate(
                    ['key' => $key],
                    [
                        'label' => $label,
                        'description' => $description,
                        'feature_id' => $locations->id,
                        'sort_order' => $sort,
                    ]
                );
            }
        }
    }
}
