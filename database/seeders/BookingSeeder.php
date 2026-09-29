<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * No `otres-bay` booking here (removed 2026-09-29): the Sihanoukville
 * placeholder demo hotels (Otres Bay Resort, Harbor Light Hotel, Coral
 * Inn) were removed from HotelSeeder the same day a real client property
 * (Greyon Hotel & Serviced Apartment, see GreyonShvSeeder) took over that
 * destination -- see HotelSeeder's docblock. GRY-SEED02, the one seed
 * booking that referenced Otres Bay Resort, was removed along with it;
 * GRY-SEED01/03/04 and the GRY-DEMO* booking only ever referenced
 * `riverside` (Phnom Penh) and are unaffected.
 */
class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $guest = User::where('email', 'guest@greyon.test')->first();

        $riversideDeluxe = RoomType::where('slug', 'deluxe-king')
            ->whereHas('hotel', fn ($q) => $q->where('slug', 'riverside'))
            ->firstOrFail();
        $riversideTwin = RoomType::where('slug', 'standard-twin')
            ->whereHas('hotel', fn ($q) => $q->where('slug', 'riverside'))
            ->firstOrFail();

        $flex = RatePlan::where('room_type_id', $riversideDeluxe->id)
            ->where('name', 'Flexible Rate')
            ->firstOrFail();
        $twinRate = RatePlan::where('room_type_id', $riversideTwin->id)
            ->where('name', 'Flexible Rate')
            ->firstOrFail();

        $seeds = [
            [
                'reference' => 'GRY-SEED01',
                'user_id' => $guest?->id,
                'hotel_id' => $riversideDeluxe->hotel_id,
                'room_type_id' => $riversideDeluxe->id,
                'rate_plan_id' => $flex->id,
                'check_in' => now()->addDays(10)->toDateString(),
                'check_out' => now()->addDays(12)->toDateString(),
                'rooms' => 1,
                'adults' => 2,
                'children' => 0,
                'guest_full_name' => $guest?->name ?? 'Test Guest',
                'guest_email' => $guest?->email ?? 'guest@greyon.test',
                'guest_phone' => '+855 23 000 111',
                'subtotal' => 130.00,
                'taxes_fees' => 19.50,
                'total' => 149.50,
                'status' => 'confirmed',
                'source' => 'website',
            ],
            [
                'reference' => 'GRY-SEED03',
                'user_id' => null,
                'hotel_id' => $riversideTwin->hotel_id,
                'room_type_id' => $riversideTwin->id,
                'rate_plan_id' => $twinRate->id,
                'check_in' => now()->addDays(3)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'rooms' => 1,
                'adults' => 1,
                'children' => 0,
                'guest_full_name' => 'Walk-in Guest',
                'guest_email' => 'walkin@example.com',
                'guest_phone' => '+855 12 999 888',
                'subtotal' => 80.00,
                'taxes_fees' => 12.00,
                'total' => 92.00,
                'status' => 'pending',
                'source' => 'website',
                'notes' => 'Awaiting hotel confirmation.',
            ],
            [
                'reference' => 'GRY-SEED04',
                'user_id' => null,
                'hotel_id' => $riversideDeluxe->hotel_id,
                'room_type_id' => $riversideDeluxe->id,
                'rate_plan_id' => $flex->id,
                'check_in' => now()->addDays(1)->toDateString(),
                'check_out' => now()->addDays(2)->toDateString(),
                'rooms' => 2,
                'adults' => 3,
                'children' => 0,
                'guest_full_name' => 'Admin Created',
                'guest_email' => 'ops@example.com',
                'guest_phone' => '+855 23 555 000',
                'subtotal' => 130.00,
                'taxes_fees' => 19.50,
                'total' => 149.50,
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
                'hotel_id' => $riversideDeluxe->hotel_id,
                'room_type_id' => $riversideDeluxe->id,
                'rate_plan_id' => $flex->id,
                'check_in' => now()->addDays(7)->toDateString(),
                'check_out' => now()->addDays(9)->toDateString(),
                'rooms' => 1,
                'adults' => 2,
                'children' => 0,
                'guest_full_name' => 'Demo Traveller',
                'guest_email' => 'demo.traveller@example.com',
                'guest_phone' => '+855 11 222 333',
                'subtotal' => 130.00,
                'taxes_fees' => 19.50,
                'total' => 149.50,
                'status' => 'pending',
                'source' => 'website',
            ]);
        }
    }
}
