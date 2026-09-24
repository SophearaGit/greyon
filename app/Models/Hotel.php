<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A property under a Location. Scope is enforced via
 * App\Services\AccessService::canAccessHotel() against a hotel-scoped
 * package's `hotel_ids`, or a location-scoped package's `location_ids`
 * (via this hotel's `location_id`) — both live on the `admin_package`
 * pivot for that specific package assignment (App\Models\AdminPackage),
 * not on the admin itself.
 */
class Hotel extends Model
{
    protected $fillable = [
        'location_id',
        'name',
        'slug',
        'short_description',
        'description',
        'address',
        'lat',
        'lng',
        'map_embed_url',
        'phone',
        'email',
        'hero_image',
        'gallery',
        'amenities',
        'policies',
        'check_in_time',
        'check_out_time',
        'featured',
        'status',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'location_id' => 'integer',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'gallery' => 'array',
            'amenities' => 'array',
            'policies' => 'array',
            'featured' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<RoomType, $this>
     */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
