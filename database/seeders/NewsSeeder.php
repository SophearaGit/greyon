<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title' => 'Quiet mornings on the Tonle Sap',
                'slug' => 'quiet-mornings-tonle-sap',
                'cover_image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
                'excerpt' => 'A riverside stay in Phnom Penh for travellers who like the city soft.',
                'body' => "Greyon’s riverside hotels open onto the Tonle Sap with long breakfast hours and shade by the water.\n\nAsk the front desk for early boat crossings or a quiet table overlooking Sisowath Quay.",
                'published_at' => now()->subDays(12)->toDateString(),
                'status' => 'published',
                'seo_title' => 'Quiet mornings on the Tonle Sap | Greyon',
                'seo_description' => 'Riverside stays and calm mornings with Greyon in Phnom Penh.',
            ],
            [
                'title' => 'Angkor at golden hour',
                'slug' => 'angkor-golden-hour',
                'cover_image' => 'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?auto=format&fit=crop&w=1400&q=80',
                'excerpt' => 'How we time temple days so guests return cool and unhurried.',
                'body' => "Siem Reap stays work best with an early temple start and a long afternoon by the pool.\n\nOur concierge can arrange licensed guides and late check-out after sunrise visits.",
                'published_at' => now()->subDays(5)->toDateString(),
                'status' => 'published',
                'seo_title' => 'Angkor at golden hour | Greyon',
                'seo_description' => 'Temple timing tips for Greyon guests in Siem Reap.',
            ],
            [
                'title' => 'Draft: coast portfolio notes',
                'slug' => 'draft-coast-portfolio',
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
