<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-rate-plan price / stay-rule override for a single date.
 * Scope via rate plan → room type → hotel through AccessService::canAccessHotel().
 */
class RateCalendar extends Model
{
    protected $fillable = [
        'rate_plan_id',
        'date',
        'price',
        'min_stay',
        'max_stay',
    ];

    protected function casts(): array
    {
        return [
            'rate_plan_id' => 'integer',
            'date' => 'date:Y-m-d',
            'price' => 'decimal:2',
            'min_stay' => 'integer',
            'max_stay' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RatePlan, $this>
     */
    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }
}
