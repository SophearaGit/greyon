<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Services catalog — the client's 2026-10-04 requirements doc,
     * item 6: "new 'Services' list, managed and selected the same way
     * as Products, replacing the current free-text service field on
     * the hotel form." (Products itself, item 5, is a separate round —
     * not built here.)
     *
     * A flat, shared catalog (like Features/Permissions, not scoped to
     * one location/hotel) — any hotel can select from it via the
     * `hotel_service` pivot (see the next migration). `status` lets a
     * service be retired (draft/archived) without deleting it and
     * silently dropping it off every hotel that references it.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
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
        Schema::dropIfExists('services');
    }
};
