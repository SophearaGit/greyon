<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Used by the admin catalog endpoints and nested in HotelResource
 * (`services` / `products`). `hotelsCount` only appears when the
 * query loaded it (admin list/show), never on the public hotel payload.
 *
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
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
            'imageUrl' => $this->image_url,
            'price' => $this->price !== null ? (float) $this->price : null,
            'currency' => $this->currency,
            'sortOrder' => (int) ($this->sort_order ?? 0),
            'status' => $this->status,
            'hotelsCount' => $this->whenCounted('hotels'),
        ];
    }
}
