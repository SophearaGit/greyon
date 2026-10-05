<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hotels <-> Services, many-to-many. `cascadeOnDelete` on both
     * sides — deliberately different from the `restrictOnDelete`
     * convention used for true parent-child ownership elsewhere in
     * this schema (location -> hotel, hotel -> room_type): a service
     * is a shared tag a hotel opts into, not something it owns, so
     * deleting either side should just drop the association, not be
     * blocked by it or cascade-delete the other record.
     */
    public function up(): void
    {
        Schema::create('hotel_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['hotel_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_service');
    }
};
