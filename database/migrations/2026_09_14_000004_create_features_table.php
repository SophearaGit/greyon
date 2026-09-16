<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The feature catalog (spec section 3), CRUD-able by developer
     * (App\Http\Controllers\Developer\FeatureController). `key` is what
     * App\Services\AccessService::can() and the `permission:<key>` route
     * middleware check against — packages grant a set of these to
     * whoever holds them. This is module-level visibility only; finer-
     * grained capability within a module is `permissions` (see
     * 2026_09_16_000003_create_permissions_table.php) — `parent_key`
     * used to serve that purpose informally (a feature could name
     * another feature as its "parent") until this round replaced it
     * with a real `permissions` table + `feature_id` FK, so that column
     * never shipped in the final shape (removed here in place, same
     * "nothing's migrated on a real DB with the old shape yet"
     * reasoning used throughout this project's history).
     */
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->enum('category', ['admin', 'public'])->default('admin');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
