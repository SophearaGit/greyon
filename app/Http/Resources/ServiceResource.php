<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Used by the admin catalog endpoints and nested in HotelResource
 * (`services` / `products`). `hotelsCount` only appears when the
 * query loaded it (admin list/show), never on the public hotel payload.
 *
 * @mixin \App\Models\Service
 */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'sortOrder' => (int) ($this->sort_order ?? 0),
            'status' => $this->status,
            'hotelsCount' => $this->whenCounted('hotels'),
        ];
    }
}
