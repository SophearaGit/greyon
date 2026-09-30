<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The client's "Destination Information" doc (per destination, not
     * per hotel) lists a phone and email for the destination itself,
     * separate from a specific hotel's own `phone`/`email` (which
     * `hotels` already had from Round 4). `locations` had nowhere to
     * put those until now.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('description');
            $table->string('email')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email']);
        });
    }
};
