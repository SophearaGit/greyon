<?php

namespace Database\Seeders;

use App\Models\RatePlan;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

/**
 * 4 rate plans across the 3 seeded room types — Riverside's Deluxe
 * King gets 2 (proving one room type can hold several plans), the
 * other two room types get 1 each. Must run after RoomTypeSeeder.
 */
class RatePlanSeeder extends Seeder
{
    public function run(): void
    {
        $riversideDeluxeKing = RoomType::where('hotel_id', function ($query) {
            $query->select('id')->from('hotels')->where('slug', 'riverside');
        })->where('slug', 'deluxe-king')->firstOrFail();

        $riversideStandardTwin = RoomType::where('hotel_id', function ($query) {
            $query->select('id')->from('hotels')->where('slug', 'riverside');
        })->where('slug', 'standard-twin')->firstOrFail();

        $angkorDeluxeKing = RoomType::where('hotel_id', function ($query) {
            $query->select('id')->from('hotels')->where('slug', 'angkor');
        })->where('slug', 'deluxe-king')->firstOrFail();

        RatePlan::firstOrCreate(
            ['room_type_id' => $riversideDeluxeKing->id, 'name' => 'Flexible Rate'],
            [
                'description' => 'Breakfast included, free cancellation.',
                'meal_benefit' => 'Breakfast included',
                'cancellation_policy' => 'Free cancellation until 24h before check-in',
                'base_price' => 65.00,
                'tax_percent' => 10,
                'service_fee_percent' => 5,
                'status' => 'published',
            ]
        );

        RatePlan::firstOrCreate(
            ['room_type_id' => $riversideDeluxeKing->id, 'name' => 'Non-Refundable Rate'],
            [
                'description' => 'Lower price, no changes or cancellations.',
                'meal_benefit' => null,
                'cancellation_policy' => 'Non-refundable',
                'base_price' => 55.00,
                'tax_percent' => 10,
                'service_fee_percent' => 5,
                'status' => 'published',
            ]
        );

        RatePlan::firstOrCreate(
            ['room_type_id' => $riversideStandardTwin->id, 'name' => 'Standard Rate'],
            [
                'description' => 'Breakfast included.',
                'meal_benefit' => 'Breakfast included',
                'cancellation_policy' => 'Free cancellation until 48h before check-in',
                'base_price' => 40.00,
                'tax_percent' => 10,
                'service_fee_percent' => 5,
                'status' => 'published',
            ]
        );

        RatePlan::firstOrCreate(
            ['room_type_id' => $angkorDeluxeKing->id, 'name' => 'Standard Rate'],
            [
                'description' => 'Breakfast included.',
                'meal_benefit' => 'Breakfast included',
                'cancellation_policy' => 'Free cancellation until 24h before check-in',
                'base_price' => 70.00,
                'tax_percent' => 10,
                'service_fee_percent' => 5,
                'status' => 'published',
            ]
        );
    }
}
