<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Spec section 8's "Room types" (perm `rooms`) — top of the
     * `Hotel → RoomType → RatePlan / Availability / RateCalendar /
     * Booking` chain. Scope is enforced via `hotel_id` through
     * App\Services\AccessService::canAccessHotel() (same as
     * App\Models\Hotel — no separate scope columns needed here).
     *
     * `slug` is unique *per hotel* (`hotel_id` + `slug`), not globally
     * like `locations.slug`/`hotels.slug` — unlike a location or hotel,
     * many different hotels will legitimately reuse the same room-type
     * name ("Deluxe King", "Standard Twin"), so a global-unique slug
     * would be actively wrong here.
     *
     * `restrictOnDelete` on `hotel_id`, same instinct as `hotels.location_id`.
     */
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->string('bed_type')->nullable();
            $table->string('room_size')->nullable();
            $table->unsignedInteger('max_adults')->default(1);
            $table->unsignedInteger('max_children')->default(0);
            $table->unsignedInteger('max_guests')->default(1);
            $table->json('amenities')->nullable();
            $table->unsignedInteger('base_inventory')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();

            $table->unique(['hotel_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
