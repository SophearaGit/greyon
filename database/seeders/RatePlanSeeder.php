<?php

namespace Database\Seeders;

use App\Models\RatePlan;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

/**
 * One flexible + one saver plan per room type.
 */
class RatePlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoomType::query()->with('hotel')->orderBy('id')->get() as $room) {
            $base = match ($room->slug) {
                'deluxe-king' => 65.00,
                'standard-twin' => 42.00,
                'family-suite' => 95.00,
                'garden-view' => 55.00,
                default => 50.00,
            };

            RatePlan::updateOrCreate(
                ['room_type_id' => $room->id, 'name' => 'Flexible Rate'],
                [
                    'description' => 'Breakfast included, free cancellation.',
                    'meal_benefit' => 'Breakfast included',
                    'cancellation_policy' => 'Free cancellation until 24h before check-in',
                    'base_price' => $base,
                    'tax_percent' => 10,
                    'service_fee_percent' => 5,
                    'status' => 'published',
                ]
            );

            RatePlan::updateOrCreate(
                ['room_type_id' => $room->id, 'name' => 'Saver Rate'],
                [
                    'description' => 'Lower price, limited changes.',
                    'meal_benefit' => null,
                    'cancellation_policy' => 'Non-refundable',
                    'base_price' => round($base * 0.85, 2),
                    'tax_percent' => 10,
                    'service_fee_percent' => 5,
                    'status' => 'published',
                ]
            );
        }
    }
}
