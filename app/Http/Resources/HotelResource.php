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
            'propertyType' => $this->property_type,
            'starRating' => $this->star_rating !== null ? (int) $this->star_rating : null,
            'slug' => $this->slug,
            'shortDescription' => $this->short_description,
            'description' => $this->description,
            'address' => $this->address,
            'area' => $this->area,
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
<<<<<<< Updated upstream
                        'nearbyLandmarks' => collect($this->nearby_landmarks ?? [])
                ->map(fn ($row) => [
                    'place' => is_array($row) ? ($row['place'] ?? '') : '',
                    'distance' => is_array($row) ? ($row['distance'] ?? '') : '',
                ])
                ->filter(fn ($row) => filled($row['place']))
                ->values()
                ->all(),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
>>>>>>> Stashed changes
            'checkInTime' => $this->check_in_time,
            'checkOutTime' => $this->check_out_time,
            'featured' => (bool) $this->featured,
            'status' => $this->status,
            'seoTitle' => $this->seo_title,
            'seoDescription' => $this->seo_description,
        ];
    }
}
