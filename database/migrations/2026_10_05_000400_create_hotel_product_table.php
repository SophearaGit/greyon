<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hotels <-> Products, many-to-many. `cascadeOnDelete` on both
     * sides, same reasoning as `hotel_service`: a product is a shared
     * catalog item a hotel opts into, not something it owns, so
     * deleting either side should just drop the association, not be
     * blocked by it or cascade-delete the other record.
     */
    public function up(): void
    {
        Schema::create('hotel_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['hotel_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_product');
    }
};
