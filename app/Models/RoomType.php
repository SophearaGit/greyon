<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A room category under a Hotel ("Deluxe King", "Standard Twin", ...)
 * — top of the `Hotel → RoomType → RatePlan/Availability/RateCalendar/
 * Booking` chain (spec section 4). Scope is enforced via `hotel_id`
 * through App\Services\AccessService::canAccessHotel() — same pattern
 * as App\Models\Hotel, no scope columns of its own.
 */
class RoomType extends Model
{
    protected $fillable = [
        'hotel_id',
        'name',
        'slug',
        'description',
        'images',
        'bed_type',
        'room_size',
        'max_adults',
        'max_children',
        'max_guests',
        'amenities',
        'base_inventory',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hotel_id' => 'integer',
            'images' => 'array',
            'amenities' => 'array',
            'max_adults' => 'integer',
            'max_children' => 'integer',
            'max_guests' => 'integer',
            'base_inventory' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
