<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRUD-able by developer (App\Http\Controllers\Developer\RoleController)
     * — this is what makes role assignment "flexible for us developer to
     * allowance usage for admin" rather than a fixed code enum. `name`
     * is the identifier packages attach to. `is_global` marks a role
     * (conventionally `admin`) as seeing everything regardless of any
     * package's location/hotel scope. `scope` is what makes the *rest*
     * of the scoping schema-driven rather than name-matched: `none`
     * (default), `location` (a package granting this role is scoped by
     * that assignment's `locationIds` — e.g. `manager`), or `hotel`
     * (scoped by that assignment's `hotelIds` — e.g. `hotel_admin`) —
     * see App\Services\AccessService::canAccessHotel/canAccessLocation,
     * which read this column instead of comparing role names. The
     * actual ids live on the `admin_package` pivot per assignment, not
     * on this table.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_global')->default(false);
            $table->enum('scope', ['none', 'location', 'hotel'])->default('none');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
