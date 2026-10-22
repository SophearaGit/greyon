<?php

namespace Tests\Feature;

use Database\Seeders\SeedAssetsSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeedAssetsSeederTest extends TestCase
{
    public function test_copies_seed_assets_to_public_disk(): void
    {
        Storage::fake('public');

        $this->seed(SeedAssetsSeeder::class);

        Storage::disk('public')->assertExists('hotels/greyon-pp/2br-80sqm-layout-a/01.jpg');
        Storage::disk('public')->assertExists('hotels/greyon-pp/2br-80sqm-layout-a/10.jpg');
        Storage::disk('public')->assertExists('hotels/greyon-pp/2br-80sqm-layout-b/15.jpg');
        Storage::disk('public')->assertExists('hotels/greyon-shv/hotel/01.jpg');
        Storage::disk('public')->assertExists('hotels/greyon-shv/dorm-8-bunk/04.jpg');
        $this->assertCount(57, Storage::disk('public')->allFiles('hotels'));
    }

    public function test_is_idempotent_and_does_not_overwrite_matching_files(): void
    {
        Storage::fake('public');

        $this->seed(SeedAssetsSeeder::class);
        $path = 'hotels/greyon-pp/2br-80sqm-layout-b/02.jpg';
        $size = Storage::disk('public')->size($path);

        $this->seed(SeedAssetsSeeder::class);

        $this->assertSame($size, Storage::disk('public')->size($path));
    }
}