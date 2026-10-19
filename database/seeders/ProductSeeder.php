<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter catalog for the new Products picker (client requirements
 * doc, 2026-10-04, item 5). The doc doesn't define what a "product"
 * is beyond "selected the same way as Services" — seeded here as
 * purchasable add-on packages/upsells (as opposed to Services, which
 * are free included amenities). Deliberately NOT attached to the real
 * Greyon SHV hotel here, same reasoning as ServiceSeeder's docblock:
 * whoever owns that property's admin record should pick its actual
 * products themselves once this ships, not inherit a guessed list.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            'Honeymoon Package',
            'Family Package',
            'Business Traveler Package',
            'Airport Transfer Package',
            'Spa & Wellness Package',
            'Half Board Meal Plan',
            'Full Board Meal Plan',
            'Late Checkout Upgrade',
            'Early Check-in Upgrade',
            'Room Upgrade Package',
            'Welcome Drink & Fruit Basket',
            'City Tour Package',
        ];

        foreach ($products as $name) {
            Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'status' => 'published']
            );
        }
    }
}
