<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Spec section 4's `hotels` (belongs to a location). `lat`/`lng`
     * are flat columns; the spec's `coordinates: { lat, lng }` shape is
     * assembled/accepted in HotelResource / HotelController instead of
     * stored as nested JSON, so they stay queryable.
     *
     * `restrictOnDelete` on `location_id`: deleting a location with
     * hotels under it should fail loudly, not cascade-delete a hotel's
     * data — same instinct as `admin_package`'s restrict.
     */
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('hero_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('amenities')->nullable();
            $table->json('policies')->nullable();
            $table->string('check_in_time')->nullable();
            $table->string('check_out_time')->nullable();
            $table->boolean('featured')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
