<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RateCalendar
 */
class RateCalendarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ratePlanId' => $this->rate_plan_id,
            'date' => $this->date?->format('Y-m-d'),
            'price' => $this->price !== null ? (float) $this->price : null,
            'minStay' => $this->min_stay,
            'maxStay' => $this->max_stay,
        ];
    }
}
