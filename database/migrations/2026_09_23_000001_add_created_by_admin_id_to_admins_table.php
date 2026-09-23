<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Same reasoning as `2026_09_16_000002_add_created_by_admin_id_to_
     * locations_table`: enforcing a per-admin `managers` package limit
     * ("an admin may add at most N people via People/Team") needs to
     * count *this admin's own* creations, which means admin accounts
     * need to record who created them. Nullable — every admin seeded
     * before this column existed, every developer-created admin
     * (Developer\AdminController never sets this — developers bypass
     * package limits entirely), and any admin created directly via
     * tinker/seeder has no creator and never counts against anyone's
     * quota.
     *
     * `nullOnDelete`, not `restrictOnDelete`: attribution, not a real
     * dependency — deleting an admin should never be blocked by (or
     * cascade into) people they once added.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->foreignId('created_by_admin_id')->nullable()->after('id')
                ->constrained('admins')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_admin_id');
        });
    }
};
