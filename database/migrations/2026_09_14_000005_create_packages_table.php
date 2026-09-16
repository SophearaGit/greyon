<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A named bundle of roles + features a developer assembles once and
     * assigns to many admins (`admin_package`) — CRUD-able by developer
     * (App\Http\Controllers\Developer\PackageController). `is_system`
     * marks a seeded package that shouldn't be deletable, same idea as
     * the original handoff spec's packages table.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('price_note')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
