<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * New table, developer's ask (2026-09-16): "admin can add location
     * and hotel, but we only allow him to add 3 locations with 1 hotel
     * in each — add a table permission connected with the package."
     * Named `package_limits` rather than reusing "permission" — this
     * app already has a distinct meaning for that word
     * (`features`/`EnsurePermission`/`can()`, a yes/no gate), and this
     * is a different kind of thing: a *quantity cap* on a package,
     * dynamic and developer-CRUD-able the same way roles/features are,
     * rather than a boolean.
     *
     * `resource_key` is a free string, not an FK to `features` — not
     * every capped resource maps 1:1 to a single feature key (see
     * `hotels_per_location`, which caps something reachable via the
     * `hotels` feature but isn't itself a feature), and not every
     * feature needs a cap. Each controller that enforces a limit knows
     * which feature key it's paired with — see
     * App\Services\AccessService::effectiveLimit().
     *
     * `max_count` nullable = this package's row for that key imposes
     * no cap (a package can also just have no row at all for a given
     * key, which means the same thing — see effectiveLimit()'s
     * docblock for how the two are actually the same case in practice).
     *
     * `cascadeOnDelete` on `package_id`: a limit means nothing once its
     * package is gone, unlike `admin_package`'s `restrictOnDelete` on
     * `package_id` (an *assignment* blocking deletion is intentional —
     * see that migration — a *limit definition* isn't a reason to keep
     * a package around).
     */
    public function up(): void
    {
        Schema::create('package_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('resource_key');
            $table->unsignedInteger('max_count')->nullable();
            $table->timestamps();

            $table->unique(['package_id', 'resource_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_limits');
    }
};
