<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

/**
 * Several room types per hotel so inventory / booking demos stay rich.
 */
class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'deluxe-king',
                'name' => 'Deluxe King',
                'description' => 'Spacious king room with a calm outlook.',
                'bed_type' => 'King',
                'room_size' => '32 sqm',
                'max_adults' => 2,
                'max_children' => 1,
                'max_guests' => 3,
                'amenities' => ['Air conditioning', 'Minibar', 'Work desk'],
                'base_inventory' => 8,
            ],
            [
                'slug' => 'standard-twin',
                'name' => 'Standard Twin',
                'description' => 'Two single beds for friends or colleagues.',
                'bed_type' => 'Twin',
                'room_size' => '24 sqm',
                'max_adults' => 2,
                'max_children' => 0,
                'max_guests' => 2,
                'amenities' => ['Air conditioning', 'Free WiFi'],
                'base_inventory' => 12,
            ],
            [
                'slug' => 'family-suite',
                'name' => 'Family Suite',
                'description' => 'Extra space for families — living area and two beds.',
                'bed_type' => 'King + Twin',
                'room_size' => '45 sqm',
                'max_adults' => 3,
                'max_children' => 2,
                'max_guests' => 5,
                'amenities' => ['Air conditioning', 'Sofa', 'Mini kitchenette'],
                'base_inventory' => 4,
            ],
            [
                'slug' => 'garden-view',
                'name' => 'Garden View',
                'description' => 'Quiet room facing the garden or courtyard.',
                'bed_type' => 'Queen',
                'room_size' => '28 sqm',
                'max_adults' => 2,
                'max_children' => 1,
                'max_guests' => 3,
                'amenities' => ['Air conditioning', 'Garden view', 'Free WiFi'],
                'base_inventory' => 6,
            ],
        ];

        foreach (Hotel::query()->orderBy('id')->get() as $hotel) {
            foreach ($templates as $room) {
                RoomType::updateOrCreate(
                    ['hotel_id' => $hotel->id, 'slug' => $room['slug']],
                    [
                        'name' => $room['name'],
                        'description' => $room['description'],
                        'bed_type' => $room['bed_type'],
                        'room_size' => $room['room_size'],
                        'max_adults' => $room['max_adults'],
                        'max_children' => $room['max_children'],
                        'max_guests' => $room['max_guests'],
                        'amenities' => $room['amenities'],
                        'base_inventory' => $room['base_inventory'],
                        'status' => 'published',
                    ]
                );
            }
        }
    }
}
