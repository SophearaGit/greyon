<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('password');
        });

        // A user who signs up via Google only (never sets a password) has
        // nothing to hash into this column. Plain SQL here instead of
        // Schema::table(...)->change() so we don't need doctrine/dbal
        // installed just to relax one column's NOT NULL constraint.
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Anyone with a null password would violate the NOT NULL we're
        // restoring — only relevant if you're rolling all the way back on
        // a database that already has Google-only accounts in it.
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
