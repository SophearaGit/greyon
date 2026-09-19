<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\MediaItem;
use Illuminate\Database\Seeder;

/**
 * Site media library — Unsplash stills themed to Greyon destinations
 * (Phnom Penh, Sihanoukville, Kampot). No hotel FK; context lives in alt.
 */
class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = Admin::where('email', 'admin@greyon.com.kh')->value('id');

        $items = [
            [
                'src' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Riverside Hotel · Phnom Penh riverfront pool',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Capitol Suites · modern lobby Phnom Penh',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Mekong House · boutique courtyard',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Otres Bay Resort · Sihanoukville beach',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1571008887538-b36bb32f4571?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Harbor Light Hotel · pier and sunset',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Coral Inn · Otres sand and shade',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1540541338287-41700207dee6?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Pepper House · Kampot river deck',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Bokor View Lodge · mountain outlook',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Salt Field Inn · countryside morning',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Greyon suite · king bed and soft light',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Greyon breakfast · riverside terrace',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1400&q=80',
                'alt' => 'Greyon spa · calm indoor pool',
            ],
        ];

        foreach ($items as $row) {
            MediaItem::updateOrCreate(
                ['src' => $row['src']],
                [
                    'alt' => $row['alt'],
                    'created_by_admin_id' => $adminId,
                ]
            );
        }
    }
}
