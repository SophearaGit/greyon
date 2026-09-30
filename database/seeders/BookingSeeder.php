<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Re-pointed to the real property, `greyon-shv` (2026-09-30): these
 * demo bookings used to reference `riverside`, a Phnom Penh placeholder
 * hotel. HotelSeeder's whole dummy catalog (Phnom Penh + Kampot) was
 * removed the same day so testing isn't mixing fake listings with the
 * one real property (Greyon Hotel & Serviced Apartment — see
 * GreyonShvSeeder) -- these bookings move to real room types instead of
 * disappearing, so there's still something to test admin/booking views
 * against. `taxes_fees` is 0 on every seed below, not a mistake: the
 * real property's rate plans carry 0% tax/service fee (no figure was in
 * the client's doc — see GreyonShvSeeder's docblock), so a seeded
 * booking against real rates correctly shows no tax/fee line yet.
 *
 * No `otres-bay` booking here either (removed 2026-09-29, before this):
 * see git history for that change.
 */
class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $guest = User::where('email', 'guest@greyon.test')->first();

        $roomA = RoomType::where('slug', '1br-middle')
            ->whereHas('hotel', fn ($q) => $q->where('slug', 'greyon-shv'))
            ->firstOrFail();
        $roomB = RoomType::where('slug', '1br-balcony')
            ->whereHas('hotel', fn ($q) => $q->where('slug', 'greyon-shv'))
            ->firstOrFail();

        $rateA = RatePlan::where('room_type_id', $roomA->id)
            ->where('name', 'Nightly Rate')
            ->firstOrFail();
        $rateB = RatePlan::where('room_type_id', $roomB->id)
            ->where('name', 'Nightly Rate')
            ->firstOrFail();

        $seeds = [
            [
                'reference' => 'GRY-SEED01',
                'user_id' => $guest?->id,
                'hotel_id' => $roomA->hotel_id,
                'room_type_id' => $roomA->id,
                'rate_plan_id' => $rateA->id,
                'check_in' => now()->addDays(10)->toDateString(),
                'check_out' => now()->addDays(12)->toDateString(),
                'rooms' => 1,
                'adults' => 2,
                'children' => 0,
                'guest_full_name' => $guest?->name ?? 'Test Guest',
                'guest_email' => $guest?->email ?? 'guest@greyon.test',
                'guest_phone' => '+855 23 000 111',
                'subtotal' => 80.00,
                'taxes_fees' => 0.00,
                'total' => 80.00,
                'status' => 'confirmed',
                'source' => 'website',
            ],
            [
                'reference' => 'GRY-SEED03',
                'user_id' => null,
                'hotel_id' => $roomB->hotel_id,
                'room_type_id' => $roomB->id,
                'rate_plan_id' => $rateB->id,
                'check_in' => now()->addDays(3)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'rooms' => 1,
                'adults' => 1,
                'children' => 0,
                'guest_full_name' => 'Walk-in Guest',
                'guest_email' => 'walkin@example.com',
                'guest_phone' => '+855 12 999 888',
                'subtotal' => 90.00,
                'taxes_fees' => 0.00,
                'total' => 90.00,
                'status' => 'pending',
                'source' => 'website',
                'notes' => 'Awaiting hotel confirmation.',
            ],
            [
                'reference' => 'GRY-SEED04',
                'user_id' => null,
                'hotel_id' => $roomA->hotel_id,
                'room_type_id' => $roomA->id,
                'rate_plan_id' => $rateA->id,
                'check_in' => now()->addDays(1)->toDateString(),
                'check_out' => now()->addDays(2)->toDateString(),
                'rooms' => 2,
                'adults' => 3,
                'children' => 0,
                'guest_full_name' => 'Admin Created',
                'guest_email' => 'ops@example.com',
                'guest_phone' => '+855 23 555 000',
                'subtotal' => 80.00,
                'taxes_fees' => 0.00,
                'total' => 80.00,
                'status' => 'pending',
                'source' => 'admin',
            ],
        ];

        foreach ($seeds as $row) {
            Booking::updateOrCreate(
                ['reference' => $row['reference']],
                $row
            );
        }

        // Extra random-looking pending for admin inbox demos
        if (! Booking::where('reference', 'like', 'GRY-DEMO%')->exists()) {
            Booking::create([
                'reference' => 'GRY-DEMO'.Str::upper(Str::random(4)),
                'hotel_id' => $roomA->hotel_id,
                'room_type_id' => $roomA->id,
                'rate_plan_id' => $rateA->id,
                'check_in' => now()->addDays(7)->toDateString(),
                'check_out' => now()->addDays(9)->toDateString(),
                'rooms' => 1,
                'adults' => 2,
                'children' => 0,
                'guest_full_name' => 'Demo Traveller',
                'guest_email' => 'demo.traveller@example.com',
                'guest_phone' => '+855 11 222 333',
                'subtotal' => 80.00,
                'taxes_fees' => 0.00,
                'total' => 80.00,
                'status' => 'pending',
                'source' => 'website',
            ]);
        }
    }
}
