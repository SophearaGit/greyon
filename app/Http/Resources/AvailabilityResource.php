<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Availability
 */
class AvailabilityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'roomTypeId' => $this->room_type_id,
            'date' => $this->date?->format('Y-m-d'),
            'availableUnits' => $this->available_units,
            'stopSell' => (bool) $this->stop_sell,
        ];
    }
}
