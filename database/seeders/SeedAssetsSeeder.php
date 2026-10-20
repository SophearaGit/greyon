<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Copies committed seed images from database/seeders/assets/ onto the
 * public disk at the same relative path (e.g. hotels/greyon-pp/...), so
 * URLs built by the hotel seeders resolve on fresh deploys.
 *
 * Idempotent: only writes files that are missing or differ in size.
 * Never deletes. A missing assets directory is not an error.
 */
class SeedAssetsSeeder extends Seeder
{
    public function run(?string $sourceDir = null): void
    {
        $sourceDir ??= database_path('seeders/assets');

        if (! File::isDirectory($sourceDir)) {
            $this->command?->warn("SeedAssetsSeeder: no assets directory at {$sourceDir}, skipping.");

            return;
        }

        $disk = Storage::disk('public');
        $copied = 0;
        $skipped = 0;

        foreach (File::allFiles($sourceDir) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if ($disk->exists($relative) && $disk->size($relative) === $file->getSize()) {
                $skipped++;

                continue;
            }

            $stream = fopen($file->getPathname(), 'rb');
            $disk->put($relative, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $copied++;
        }

        $this->command?->info("SeedAssetsSeeder: copied {$copied}, already present {$skipped}.");
    }
}