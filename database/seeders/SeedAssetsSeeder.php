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
 * Idempotent: only writes files that are missing or whose contents differ
 * (size, then sha1). Never deletes. Never touches uploads/. A missing
 * assets directory is not an error.
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

            // User uploads live on the same disk; seed assets must never touch them.
            if ($relative === 'uploads' || str_starts_with($relative, 'uploads/')) {
                continue;
            }

            if ($disk->exists($relative) && ! $this->differs($disk->path($relative), $file->getPathname())) {
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

    private function differs(string $target, string $source): bool
    {
        if (! is_file($target) || filesize($target) !== filesize($source)) {
            return true;
        }

        return sha1_file($target) !== sha1_file($source);
    }
}