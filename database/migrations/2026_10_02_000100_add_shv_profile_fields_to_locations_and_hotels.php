<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Greyon SHV data audit — destination contact + hotel profile fields
 * (area, property type, star rating, nearby landmarks).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('highlights');
            $table->string('email')->nullable()->after('phone');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->string('area')->nullable()->after('address');
            $table->string('property_type')->nullable()->after('area');
            $table->unsignedTinyInteger('star_rating')->nullable()->after('property_type');
            $table->json('nearby_landmarks')->nullable()->after('policies');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email']);
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn([
                'area',
                'property_type',
                'star_rating',
                'nearby_landmarks',
            ]);
        });
    }
};
