<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Needed to enforce a per-admin `locations` package limit
     * (2026-09-16): "this admin may create up to N locations" has to
     * count *this admin's own* creations, which means locations need
     * to record who created them. Nullable — every location seeded
     * before this column existed (and any created directly by a
     * developer, if that path is ever added) has no creator, and
     * simply never counts against anyone's quota.
     *
     * `nullOnDelete`, not `restrictOnDelete`: this is attribution, not
     * a real dependency — deleting an admin should never be blocked by
     * (or cascade into) locations they once created.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('created_by_admin_id')->nullable()->after('id')
                ->constrained('admins')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_admin_id');
        });
    }
};
