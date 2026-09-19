<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton-style site SEO / contact / payment settings (one seeded row).
 */
class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'default_title',
        'default_description',
        'og_image',
        'hero_slides',
        'analytics_id',
        'contact_email',
        'contact_phone',
        'site_url',
        'payment_enabled',
        'payment_note',
    ];

    protected function casts(): array
    {
        return [
            'payment_enabled' => 'boolean',
            'hero_slides' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }
}
