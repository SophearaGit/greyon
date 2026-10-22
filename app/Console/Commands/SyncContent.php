<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\Location;
use App\Models\RatePlan;
use App\Models\RoomType;
use Database\Seeders\LocationSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Targeted, idempotent content sync for a live database, so a full
 * re-seed isn't needed. It only:
 *  - sets hero_image/gallery on the phnom-penh, kampot and sihanoukville
 *    locations (matched by slug) to the committed Wikimedia photos;
 *  - publishes the greyon-pp hotel's room types and their rate plans.
 * Nothing else is touched (not the hotel row itself, not other fields).
 * Missing rows are reported and skipped, never created. Run after
 * SeedAssetsSeeder has copied the images onto the public disk.
 */
class SyncContent extends Command
{
    protected $signature = 'greyon:sync-content {--dry-run : Show what would change without writing}';

    protected $description = 'Sync destination photos and publish Phnom Penh room types/rate plans (idempotent)';

    public const LOCATION_SLUGS = ['phnom-penh', 'kampot', 'sihanoukville'];

    public const PP_HOTEL_SLUG = 'greyon-pp';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        DB::transaction(function () use ($dry) {
            foreach (self::LOCATION_SLUGS as $slug) {
                $location = Location::where('slug', $slug)->first();
                if (! $location) {
                    $this->warn("Location {$slug}: not found, skipped.");

                    continue;
                }

                $location->fill(LocationSeeder::images($slug));
                $changed = $location->isDirty();
                if ($changed && ! $dry) {
                    $location->save();
                }
                $this->line("Location {$slug}: ".($changed ? ($dry ? 'would update' : 'updated') : 'already up to date'));
            }

            $hotel = Hotel::where('slug', self::PP_HOTEL_SLUG)->first();
            if (! $hotel) {
                $this->warn('Hotel '.self::PP_HOTEL_SLUG.': not found, skipped.');

                return;
            }

            $roomTypeIds = RoomType::where('hotel_id', $hotel->id)->pluck('id');
            $rooms = RoomType::whereIn('id', $roomTypeIds)->where('status', '!=', 'published');
            $rates = RatePlan::whereIn('room_type_id', $roomTypeIds)->where('status', '!=', 'published');

            if ($dry) {
                $this->line("Room types to publish: {$rooms->count()} of {$roomTypeIds->count()}");
                $this->line("Rate plans to publish: {$rates->count()}");

                return;
            }

            $this->line('Room types published: '.$rooms->update(['status' => 'published'])." (of {$roomTypeIds->count()})");
            $this->line('Rate plans published: '.$rates->update(['status' => 'published']));
        });

        if ($dry) {
            $this->info('Dry run: nothing written.');
        }

        return self::SUCCESS;
    }
}