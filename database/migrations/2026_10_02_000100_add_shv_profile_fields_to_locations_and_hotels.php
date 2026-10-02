<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Greyon SHV data audit — destination contact + hotel profile fields
 * (area, property type, star rating, nearby landmarks).
 *
 * Main already added these in the 2026_09_30 migrations. This file stays
 * so branch databases that recorded it remain valid, and each add is
 * skipped when the column is already there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('locations', 'phone')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('highlights');
            });
        }

        if (! Schema::hasColumn('locations', 'email')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->string('email')->nullable()->after('phone');
            });
        }

        if (! Schema::hasColumn('hotels', 'area')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->string('area')->nullable()->after('address');
            });
        }

        if (! Schema::hasColumn('hotels', 'property_type')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->string('property_type')->nullable()->after('area');
            });
        }

        if (! Schema::hasColumn('hotels', 'star_rating')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->unsignedTinyInteger('star_rating')->nullable()->after('property_type');
            });
        }

        if (! Schema::hasColumn('hotels', 'nearby_landmarks')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->json('nearby_landmarks')->nullable()->after('policies');
            });
        }
    }

    public function down(): void
    {
        // Columns are owned by the 2026_09_30 migrations on main.
        // Dropping them here would remove fields those migrations still expect.
    }
};
