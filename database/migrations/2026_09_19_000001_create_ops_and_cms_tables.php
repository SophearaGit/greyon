<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ops + CMS tables for feature parity with Nest/SPA:
     * availabilities, rate_calendars, bookings, news, enquiries,
     * media_items, site_settings. Integer FKs + restrictOnDelete for
     * content chain; nullOnDelete for admin attribution on media.
     */
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->unsignedInteger('available_units');
            $table->boolean('stop_sell')->default(false);
            $table->timestamps();

            $table->unique(['room_type_id', 'date']);
        });

        Schema::create('rate_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_plan_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('min_stay')->nullable();
            $table->unsignedInteger('max_stay')->nullable();
            $table->timestamps();

            $table->unique(['rate_plan_id', 'date']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('hotel_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('rate_plan_id')->constrained()->restrictOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('rooms')->default(1);
            $table->unsignedInteger('adults');
            $table->unsignedInteger('children')->default(0);
            $table->string('guest_full_name');
            $table->string('guest_email');
            $table->string('guest_phone');
            $table->text('special_requests')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('taxes_fees', 10, 2);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->enum('source', ['website', 'admin'])->default('website');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('cover_image');
            $table->text('excerpt');
            $table->text('body');
            $table->date('published_at')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('subject');
            $table->text('message');
            $table->boolean('consent')->default(false);
            $table->enum('status', ['new', 'in_progress', 'closed'])->default('new');
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->string('src');
            $table->string('alt')->default('');
            $table->foreignId('created_by_admin_id')->nullable()
                ->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('default_title');
            $table->text('default_description');
            $table->string('og_image');
            $table->string('analytics_id')->default('');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->string('site_url');
            $table->boolean('payment_enabled')->default(false);
            $table->text('payment_note');
            $table->timestamps();
        });

        DB::table('site_settings')->insert([
            'site_name' => 'Greyon',
            'default_title' => 'Greyon | Hotels in Cambodia',
            'default_description' => 'Discover and book Greyon hotels across Cambodia.',
            'og_image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80',
            'analytics_id' => '',
            'contact_email' => 'hello@greyon.com.kh',
            'contact_phone' => '+855 23 000 000',
            'site_url' => 'https://www.greyon.com.kh',
            'payment_enabled' => false,
            'payment_note' => 'Online payment is not enabled for this MVP. Submitting creates a reservation request; payment is handled offline per hotel policy.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('media_items');
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('news');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('rate_calendars');
        Schema::dropIfExists('availabilities');
    }
};
