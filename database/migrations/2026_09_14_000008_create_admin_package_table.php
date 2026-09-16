<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. "User [admin] can have many packages" — the
     * pivot. `restrictOnDelete` on `package_id` so a developer can't
     * delete a package out from under admins currently holding it
     * without dealing with them first (spec parity: packages block
     * delete while in use, we just enforce it at the DB level too).
     *
     * `location_ids`/`hotel_ids` live here, not on `admins` — each
     * package *assignment* carries its own scope, not the admin as a
     * whole. This is what lets the same package (e.g. "Manager ·
     * Content+") be granted to two different admins scoped to two
     * different locations, and lets one admin hold several
     * differently-scoped packages at once instead of a single flat
     * locationIds/hotelIds pair applying to everything they hold. See
     * App\Models\AdminPackage (the pivot model) and
     * App\Services\AccessService, which reads these per assignment.
     */
    public function up(): void
    {
        Schema::create('admin_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->json('location_ids')->nullable();
            $table->json('hotel_ids')->nullable();
            $table->unique(['admin_id', 'package_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_package');
    }
};
