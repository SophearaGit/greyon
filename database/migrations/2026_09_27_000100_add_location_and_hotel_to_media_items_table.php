<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Media library assets belong to a destination (location), and may
 * optionally be pinned to a hotel under that destination so admins can
 * filter the library by city and property.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->after('id')
                ->constrained('locations')
                ->nullOnDelete();
            $table->foreignId('hotel_id')
                ->nullable()
                ->after('location_id')
                ->constrained('hotels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hotel_id');
            $table->dropConstrainedForeignId('location_id');
        });
    }
};
