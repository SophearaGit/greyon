<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

/**
 * Three starter home-page hero slides, only when the table is empty, so
 * admin edits are never overwritten. Images are committed seed photos
 * (database/seeders/assets/...) that SeedAssetsSeeder copies onto the
 * public disk; `image` stores the public-disk path. Copy is plain and
 * factual - no ratings, reviews or claims.
 */
class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        if (HeroSlide::query()->exists()) {
            $this->command?->info('HeroSlideSeeder: slides already exist, skipping.');

            return;
        }

        $slides = [
            [
                'title' => 'Greyon Sihanoukville',
                'subtitle' => 'Rooms, dorms and serviced apartments in Sihanoukville, gateway to Cambodia\'s southern islands.',
                'image' => 'hotels/greyon-shv/hotel/01.jpg',
                'cta_label' => 'View hotel',
                'cta_url' => '/hotels/greyon-shv',
            ],
            [
                'title' => 'Greyon Phnom Penh',
                'subtitle' => 'Serviced apartments in Phnom Penh, Cambodia\'s capital.',
                'image' => 'hotels/greyon-pp/2br-80sqm-layout-a/01.jpg',
                'cta_label' => 'View hotel',
                'cta_url' => '/hotels/greyon-pp',
            ],
            [
                'title' => 'Stay in Cambodia',
                'subtitle' => 'Find a place to stay in Phnom Penh, Kampot and Sihanoukville.',
                'image' => 'locations/kampot/hero.jpg',
                'cta_label' => 'Browse hotels',
                'cta_url' => '/hotels',
            ],
        ];

        foreach ($slides as $i => $slide) {
            HeroSlide::create([...$slide, 'sort_order' => $i, 'is_active' => true]);
        }
    }
}
