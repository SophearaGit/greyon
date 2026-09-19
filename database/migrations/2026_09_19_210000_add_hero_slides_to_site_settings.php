<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('hero_slides')->nullable()->after('og_image');
        });

        $defaults = json_encode([
            [
                'src' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2000&q=80',
                'alt' => 'Greyon resort at dusk',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=2000&q=80',
                'alt' => 'Luxury hotel pool',
            ],
            [
                'src' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=2000&q=80',
                'alt' => 'Hotel terrace overlooking water',
            ],
        ]);

        DB::table('site_settings')->update(['hero_slides' => $defaults]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('hero_slides');
        });
    }
};
