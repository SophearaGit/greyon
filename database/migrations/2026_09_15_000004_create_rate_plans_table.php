<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Spec section 8's "Rate plans" (perm `rates`) — next link in the
     * `Hotel → RoomType → RatePlan / Availability / RateCalendar /
     * Booking` chain, right after Room types. Scope is enforced via
     * `room_type_id` → `room_types.hotel_id` through
     * App\Services\AccessService::canAccessHotel() (same instinct as
     * RoomType itself — no separate scope columns needed here).
     *
     * No `slug` — the spec doesn't list one for rate plans (unlike
     * locations/hotels/room types), and there's no public single-item
     * lookup route for a rate plan by itself in section 9. No
     * uniqueness constraint on `name` either: the spec doesn't ask for
     * one, and a room type legitimately having two rate plans that
     * happen to share a name isn't described as invalid anywhere.
     *
     * `restrictOnDelete` on `room_type_id`, same instinct as
     * `room_types.hotel_id`/`hotels.location_id`.
     */
    public function up(): void
    {
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('meal_benefit')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('service_fee_percent', 5, 2)->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_plans');
    }
};
