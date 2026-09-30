<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The client's per-property info doc gives each hotel a
     * "Property Type" (e.g. "Hotel & Serviced Apartment") and a
     * "Star Rating" (e.g. 3). Neither had a dedicated column before
     * this — GreyonShvSeeder folded both into the free-text
     * `description` field instead, flagged there as a known gap.
     */
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('property_type')->nullable()->after('name');
            $table->unsignedTinyInteger('star_rating')->nullable()->after('property_type');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['property_type', 'star_rating']);
        });
    }
};
