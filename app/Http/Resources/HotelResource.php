<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Hotel
 */
class HotelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'locationId' => $this->location_id,
            'location' => $this->whenLoaded('location', fn () => [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'slug' => $this->location->slug,
            ]),
            'name' => $this->name,
            'slug' => $this->slug,
            'shortDescription' => $this->short_description,
            'description' => $this->description,
            'address' => $this->address,
            'coordinates' => [
                'lat' => $this->lat !== null ? (float) $this->lat : null,
                'lng' => $this->lng !== null ? (float) $this->lng : null,
            ],
            'mapEmbedUrl' => $this->map_embed_url,
            'phone' => $this->phone,
            'email' => $this->email,
            'heroImage' => $this->hero_image,
            'gallery' => $this->gallery ?? [],
            'amenities' => $this->amenities ?? [],
            'policies' => $this->policies ?? [],
            'checkInTime' => $this->check_in_time,
            'checkOutTime' => $this->check_out_time,
            'featured' => (bool) $this->featured,
            'status' => $this->status,
            'seoTitle' => $this->seo_title,
            'seoDescription' => $this->seo_description,
        ];
    }
}
