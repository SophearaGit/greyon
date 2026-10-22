<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Location;
use App\Models\RatePlan;
use App\Models\RoomType;
use Database\Seeders\GreyonPhnomPenhSeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncContentCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function refreshDatabase()
    {
        // Some migrations use MySQL-only SQL (ALTER ... MODIFY), so the
        // default sqlite :memory: test DB can't migrate. Run these against
        // a MySQL test connection; skip otherwise.
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Needs a MySQL test database (migrations use MySQL-only SQL).');
        }

        $this->usingInMemoryDatabase() ? $this->refreshInMemoryDatabase() : $this->refreshTestDatabase();
    }

    public function test_updates_location_photos_and_publishes_pp_rooms_only(): void
    {
        $this->seed(LocationSeeder::class);
        $this->seed(GreyonPhnomPenhSeeder::class);

        // Simulate the live DB's old values.
        Location::query()->update(['hero_image' => 'https://picsum.photos/x', 'gallery' => json_encode(['https://picsum.photos/y'])]);
        RoomType::query()->update(['status' => 'draft']);
        $room = RoomType::firstOrFail();
        $rate = RatePlan::create(['room_type_id' => $room->id, 'name' => 'Std', 'base_price' => 10, 'status' => 'draft']);
        $other = Location::create(['slug' => 'other', 'name' => 'Other', 'hero_image' => 'keep.jpg', 'status' => 'draft']);
        $hotel = Hotel::where('slug', 'greyon-pp')->firstOrFail();
        $roomCount = RoomType::count();

        $this->artisan('greyon:sync-content')->assertSuccessful();
        $this->artisan('greyon:sync-content')->assertSuccessful(); // idempotent

        $pp = Location::where('slug', 'phnom-penh')->firstOrFail();
        $this->assertStringEndsWith('/storage/locations/phnom-penh/hero.jpg', $pp->hero_image);
        $this->assertCount(3, $pp->gallery);
        $this->assertSame(0, RoomType::where('status', '!=', 'published')->count());
        $this->assertSame('published', $rate->fresh()->status);
        $this->assertSame($roomCount, RoomType::count());
        $this->assertSame('keep.jpg', $other->fresh()->hero_image);
        $this->assertSame($hotel->status, $hotel->fresh()->status);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->seed(LocationSeeder::class);
        Location::query()->update(['hero_image' => 'old.jpg']);

        $this->artisan('greyon:sync-content --dry-run')->assertSuccessful();

        $this->assertSame('old.jpg', Location::where('slug', 'kampot')->value('hero_image'));
    }

}
