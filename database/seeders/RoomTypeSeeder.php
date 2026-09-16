<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

/**
 * 3 room types across the 2 seeded hotels — enough to exercise
 * `canAccessHotel` scope filtering (hotel_admin@/pp@/sr@/angkor@ in
 * AdminSeeder) and the per-hotel-unique slug. Must run after
 * HotelSeeder.
 */
class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $riverside = Hotel::where('slug', 'riverside')->firstOrFail();
        $angkor = Hotel::where('slug', 'angkor')->firstOrFail();

        RoomType::firstOrCreate(
            ['hotel_id' => $riverside->id, 'slug' => 'deluxe-king'],
            [
                'name' => 'Deluxe King',
                'description' => 'A spacious king room with a river view.',
                'bed_type' => 'King',
                'room_size' => '32 sqm',
                'max_adults' => 2,
                'max_children' => 1,
                'max_guests' => 3,
                'amenities' => ['Air conditioning', 'Minibar', 'River view'],
                'base_inventory' => 10,
                'status' => 'published',
            ]
        );

        RoomType::firstOrCreate(
            ['hotel_id' => $riverside->id, 'slug' => 'standard-twin'],
            [
                'name' => 'Standard Twin',
                'description' => 'Two single beds, ideal for friends or colleagues.',
                'bed_type' => 'Twin',
                'room_size' => '24 sqm',
                'max_adults' => 2,
                'max_children' => 0,
                'max_guests' => 2,
                'amenities' => ['Air conditioning', 'Free WiFi'],
                'base_inventory' => 15,
                'status' => 'published',
            ]
        );

        RoomType::firstOrCreate(
            ['hotel_id' => $angkor->id, 'slug' => 'deluxe-king'],
            [
                'name' => 'Deluxe King',
                'description' => 'A quiet king room close to the pool.',
                'bed_type' => 'King',
                'room_size' => '30 sqm',
                'max_adults' => 2,
                'max_children' => 1,
                'max_guests' => 3,
                'amenities' => ['Air conditioning', 'Minibar', 'Pool view'],
                'base_inventory' => 8,
                'status' => 'published',
            ]
        );
    }
}
