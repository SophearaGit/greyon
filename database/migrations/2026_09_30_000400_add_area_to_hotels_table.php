<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The client's "Destination Information" doc gives each property
     * an "Area" within its city (e.g. "Central / Ekareach Street") —
     * a coarser label than the full street address, no existing
     * column fit it.
     */
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('area')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
