<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Products catalog — the client's 2026-10-04 requirements doc, item
     * 5: "new 'Products' section in the admin sidebar; hotels should be
     * able to select multiple products (many-to-many) — this doesn't
     * exist yet." The doc gives no further spec of what a "product" is
     * beyond that it's picked the same way as Services (item 6, which
     * explicitly says "managed and selected the same way as Products").
     * Seeded here as purchasable add-on packages/upsells (e.g. a
     * honeymoon package, an airport-transfer bundle) — distinct from
     * Services, which are free, included amenities — but that's this
     * implementation's interpretation, not something the client doc
     * pins down; confirm with the client if the intent differs.
     *
     * Same flat, shared-catalog shape as `services` (not scoped to one
     * location/hotel — any hotel can select from it via the
     * `hotel_product` pivot, see the next migration). `status` lets a
     * product be retired (draft/archived) without deleting it and
     * silently dropping it off every hotel that references it.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
