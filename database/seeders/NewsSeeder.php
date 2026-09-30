<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\News;
use Illuminate\Database\Seeder;

/**
 * No `angkor-golden-hour` article here (removed 2026-09-30, alongside
 * the `siem-reap` location -- see LocationSeeder's docblock): it was
 * Siem Reap-specific content with nowhere sensible left to attach.
 */
class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $pp = Location::where('slug', 'phnom-penh')->value('id');
        $sv = Location::where('slug', 'sihanoukville')->value('id');

        $articles = [
            [
                'title' => 'Quiet mornings on the Tonle Sap',
                'slug' => 'quiet-mornings-tonle-sap',
                'location_id' => $pp,
                'cover_image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
                'excerpt' => 'A riverside stay in Phnom Penh for travellers who like the city soft.',
                'body' => "Greyon’s riverside hotels open onto the Tonle Sap with long breakfast hours and shade by the water.\n\nAsk the front desk for early boat crossings or a quiet table overlooking Sisowath Quay.",
                'published_at' => now()->subDays(12)->toDateString(),
                'status' => 'published',
                'seo_title' => 'Quiet mornings on the Tonle Sap | Greyon',
                'seo_description' => 'Riverside stays and calm mornings with Greyon in Phnom Penh.',
            ],
            [
                'title' => 'Draft: coast portfolio notes',
                'slug' => 'draft-coast-portfolio',
                'location_id' => $sv,
                'cover_image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1400&q=80',
                'excerpt' => 'Internal notes for the coming coastal collection.',
                'body' => 'Not for public yet.',
                'published_at' => null,
                'status' => 'draft',
                'seo_title' => null,
                'seo_description' => null,
            ],
        ];

        foreach ($articles as $row) {
            News::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
