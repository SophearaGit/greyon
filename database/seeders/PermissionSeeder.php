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

        // Richer destination capabilities used by the SPA / AccessService.
        $locations = Feature::where('key', 'locations')->first();
        if ($locations) {
            $extras = [
                ['locations_detail', 'Destination detail hub', 'Open a destination and manage its hotels & room types in one place', 9],
                ['locations_managers', 'Destination managers', 'Assign managers to destinations', 10],
                ['locations_hotels', 'Hotels under destinations', 'Create and nest hotels under a destination', 11],
                ['locations_publish', 'Publish destinations', 'Publish / archive destination pages', 12],
                ['locations_seo', 'Destination SEO', 'Per-destination SEO fields', 13],
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

            // Friendlier CRUD labels for the destinations module.
            $crudLabels = [
                'locations_list' => ['Destinations: List / view', 'See destinations and open the detail hub'],
                'locations_create' => ['Destinations: Create', 'Add new destinations'],
                'locations_update' => ['Destinations: Update', 'Edit destination details and hero'],
                'locations_delete' => ['Destinations: Delete', 'Remove destinations'],
            ];
            foreach ($crudLabels as $key => [$label, $description]) {
                Permission::where('key', $key)->update([
                    'label' => $label,
                    'description' => $description,
                ]);
            }
        }
    }
}
