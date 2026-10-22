<?php

namespace Tests\Feature;

use Database\Seeders\LocationSeeder;
use Tests\TestCase;

class LocationAssetsTest extends TestCase
{
    public function test_location_image_files_exist_in_seed_assets(): void
    {
        foreach (['phnom-penh', 'kampot', 'sihanoukville'] as $slug) {
            $images = LocationSeeder::images($slug);
            $this->assertStringEndsWith("/storage/locations/{$slug}/hero.jpg", $images['hero_image']);
            $this->assertCount(LocationSeeder::GALLERY_COUNT, $images['gallery']);
            foreach (['hero', 'gallery-1', 'gallery-2', 'gallery-3'] as $name) {
                $this->assertFileExists(database_path("seeders/assets/locations/{$slug}/{$name}.jpg"));
            }
        }
        $this->assertFileExists(database_path('seeders/assets/locations/CREDITS.md'));
    }
}