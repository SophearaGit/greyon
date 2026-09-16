<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. "Packages can have many permissions" — same
     * shape as `package_feature`/`package_role`. A permission granted
     * here is à la carte: a package can grant `locations_publish`
     * without granting every other permission under the `locations`
     * feature, same way it already picks individual features/roles.
     * See App\Services\AccessService::effectivePermissionKeys() for how
     * this combines with permissions inherited through a package's
     * features.
     */
    public function up(): void
    {
        Schema::create('package_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['package_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_permission');
    }
};
