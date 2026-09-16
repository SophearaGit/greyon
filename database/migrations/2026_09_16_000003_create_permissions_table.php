<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The doc maker's sample (2026-09-16): "package -> roles, package ->
     * features, package -> permissions, feature -> permissions." This is
     * the third leg — a CRUD-able catalog of fine-grained capabilities,
     * one level under `features`. A feature is module-level visibility
     * (still the sole thing `permission:<key>` route middleware and
     * App\Services\AccessService::can() check); a permission is a
     * specific capability *within* a module a package can grant or
     * withhold independently — this formalizes what Round 8's
     * `features.parent_key` sub-feature convention did informally (see
     * 2026_09_14_000004_create_features_table.php's updated docblock).
     * `feature_id` is nullable: a permission doesn't strictly have to
     * belong to a feature to exist and be grantable via
     * `package_permission`, though in practice every seeded one does.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->foreignId('feature_id')->nullable()->constrained()->restrictOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
