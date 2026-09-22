<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Packages grant features + every permission under those features.
 * Site shape: 3 locations max, 3 hotels per location (Admin · Starter).
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $allAdminFeatures = Feature::where('category', 'admin')->pluck('key')->all();
        $allPublicFeatures = Feature::where('category', 'public')->pluck('key')->all();
        $fullFeatures = [...$allAdminFeatures, ...$allPublicFeatures];

        // Org admins get People (users) but not Seat types / Access catalog (features).
        $adminFeatures = array_values(array_diff($fullFeatures, ['features']));

        // Manager / hotel seats — no People (users) or Seat types (features).
        $coreFeatures = [
            'dashboard',
            'locations',
            'hotels',
            'rooms',
            'bookings',
            'enquiries',
            'settings',
            'booking_public',
            'contact_public',
        ];
        $contentFeatures = [...$coreFeatures, 'news', 'news_public', 'media'];
        $bookingFeatures = [...$contentFeatures, 'rates'];

        $this->makePackage(
            name: 'Admin · Full Suite',
            description: 'Org admin — locations, assign Manager / Hotel desk seats, up to 3 hotels per destination. Seats show as roles on People.',
            priceNote: 'Enterprise',
            roleNames: ['admin'],
            featureKeys: $adminFeatures,
            limits: ['locations' => 3, 'hotels_per_location' => 3],
        );

        $this->makePackage(
            name: 'Admin · Starter',
            description: 'Admin seat: 3 destinations · 3 hotels each. Assign manager & hotel desk people.',
            priceNote: 'Starter',
            roleNames: ['admin'],
            featureKeys: $adminFeatures,
            limits: ['locations' => 3, 'hotels_per_location' => 3],
        );

        $this->makePackage(
            name: 'Manager · Booking Pro',
            description: 'Location manager with rates — one or many assigned destinations (not guest accounts).',
            priceNote: 'Pro',
            roleNames: ['manager'],
            featureKeys: $bookingFeatures,
            limits: ['locations' => 3, 'hotels_per_location' => 3],
        );

        $this->makePackage(
            name: 'Manager · Content+',
            description: 'Location manager with news — no rates calendar.',
            priceNote: 'Mid',
            roleNames: ['manager'],
            featureKeys: $contentFeatures,
            limits: ['locations' => 3, 'hotels_per_location' => 3],
        );

        $this->makePackage(
            name: 'Hotel Admin · Booking Pro',
            description: 'Property seat with rates — scoped to assigned hotels.',
            priceNote: 'Pro',
            roleNames: ['hotel_admin'],
            featureKeys: array_values(array_diff($bookingFeatures, ['locations', 'settings'])),
        );

        $this->makePackage(
            name: 'Hotel Admin · Core',
            description: 'Property starter seat (no rates calendar).',
            priceNote: 'Starter',
            roleNames: ['hotel_admin'],
            featureKeys: array_values(array_diff($coreFeatures, ['locations', 'settings'])),
        );

        $this->makePackage(
            name: 'Ops · Booking + Admin',
            description: 'Combo seat: admin + manager for multi-site ops.',
            priceNote: 'Pro',
            roleNames: ['admin', 'manager'],
            featureKeys: $adminFeatures,
            limits: ['locations' => 3, 'hotels_per_location' => 3],
        );
    }

    /**
     * @param  list<string>  $roleNames
     * @param  list<string>  $featureKeys
     * @param  array<string, int>  $limits
     */
    private function makePackage(
        string $name,
        string $description,
        ?string $priceNote,
        array $roleNames,
        array $featureKeys,
        array $limits = [],
    ): void {
        $package = Package::updateOrCreate(
            ['name' => $name],
            [
                'description' => $description,
                'price_note' => $priceNote,
                'is_system' => true,
            ]
        );

        $package->roles()->sync(Role::whereIn('name', $roleNames)->pluck('id'));
        $package->features()->sync(Feature::whereIn('key', $featureKeys)->pluck('id'));

        // Grant all permissions that belong to the package's features.
        $permissionIds = Permission::query()
            ->whereHas('feature', fn ($q) => $q->whereIn('key', $featureKeys))
            ->pluck('id');
        $package->permissions()->sync($permissionIds);

        $keepKeys = array_keys($limits);
        if ($keepKeys === []) {
            $package->limits()->delete();
        } else {
            $package->limits()->whereNotIn('resource_key', $keepKeys)->delete();
        }

        foreach ($limits as $resourceKey => $maxCount) {
            $package->limits()->updateOrCreate(
                ['resource_key' => $resourceKey],
                ['max_count' => $maxCount],
            );
        }
    }
}
