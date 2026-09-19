<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-room-type inventory override for a single date.
 * Scope via room type → hotel through AccessService::canAccessHotel().
 */
class Availability extends Model
{
    protected $fillable = [
        'room_type_id',
        'date',
        'available_units',
        'stop_sell',
    ];

    protected function casts(): array
    {
        return [
            'room_type_id' => 'integer',
            'date' => 'date:Y-m-d',
            'available_units' => 'integer',
            'stop_sell' => 'boolean',
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
