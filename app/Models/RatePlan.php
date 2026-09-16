<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A priced rate under a RoomType ("Flexible Rate", "Non-Refundable
 * Rate", ...) — next link in the `Hotel → RoomType → RatePlan /
 * Availability / RateCalendar / Booking` chain (spec section 4). Scope
 * is enforced via `room_type_id` → the room type's `hotel_id` through
 * App\Services\AccessService::canAccessHotel() — same pattern as
 * App\Models\RoomType, one level further down.
 */
class RatePlan extends Model
{
    protected $fillable = [
        'room_type_id',
        'name',
        'description',
        'meal_benefit',
        'cancellation_policy',
        'base_price',
        'tax_percent',
        'service_fee_percent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'room_type_id' => 'integer',
            'base_price' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'service_fee_percent' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
