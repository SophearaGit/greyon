<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RatePlan
 */
class RatePlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'roomTypeId' => $this->room_type_id,
            'roomType' => $this->whenLoaded('roomType', fn () => [
                'id' => $this->roomType->id,
                'name' => $this->roomType->name,
                'slug' => $this->roomType->slug,
                'hotelId' => $this->roomType->hotel_id,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'mealBenefit' => $this->meal_benefit,
            'cancellationPolicy' => $this->cancellation_policy,
            'basePrice' => $this->base_price !== null ? (float) $this->base_price : null,
            'taxPercent' => $this->tax_percent !== null ? (float) $this->tax_percent : null,
            'serviceFeePercent' => $this->service_fee_percent !== null ? (float) $this->service_fee_percent : null,
            'status' => $this->status,
        ];
    }
}
