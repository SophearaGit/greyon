<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The client's per-property info doc (section 4, "Other Relevant
     * Information") lists nearby landmarks with approximate distances
     * (e.g. "China Wanda Supermarket — 50 m"). No existing column
     * fit this — `gallery`/`amenities`/`policies` are all flat string
     * lists, not place+distance pairs — so this is a new JSON column,
     * same pattern as those three: an array of
     * `{ "place": string, "distance": string }` objects.
     */
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->json('nearby_landmarks')->nullable()->after('policies');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('nearby_landmarks');
        });
    }
};
