<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive follow-up to the 2026_10_05 services/products tables
     * (client requirements doc 2026-10-04, items 5 + 6):
     *  - `sort_order` on both, so admins control the order the pickers
     *    and the public hotel page list them in.
     *  - `image_url`, `price`, `currency` on products only. The doc does
     *    not specify product fields; these are optional (nullable) so a
     *    product can be a plain label like a service or a priced add-on.
     * `status` (draft/published/archived) stays the active flag, matching
     * every other CMS resource here; no separate is_active column.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('icon');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('image_url', 2048)->nullable()->after('icon');
            $table->decimal('price', 10, 2)->nullable()->after('image_url');
            $table->char('currency', 3)->nullable()->after('price');
            $table->unsignedInteger('sort_order')->default(0)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'price', 'currency', 'sort_order']);
        });
    }
};
