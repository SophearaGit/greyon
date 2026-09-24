<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enquiry (and other non-booking) alerts need nullable booking/hotel FKs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_notifications', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['hotel_id']);
        });

        Schema::table('staff_notifications', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->change();
            $table->foreignId('hotel_id')->nullable()->change();
        });

        Schema::table('staff_notifications', function (Blueprint $table) {
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->nullOnDelete();
            $table->foreign('hotel_id')
                ->references('id')
                ->on('hotels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_notifications', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['hotel_id']);
        });

        Schema::table('staff_notifications', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable(false)->change();
            $table->foreignId('hotel_id')->nullable(false)->change();
        });

        Schema::table('staff_notifications', function (Blueprint $table) {
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->cascadeOnDelete();
            $table->foreign('hotel_id')
                ->references('id')
                ->on('hotels')
                ->restrictOnDelete();
        });
    }
};
