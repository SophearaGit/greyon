<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Location
 */
class LocationResource extends JsonResource
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
            'heroImage' => $this->hero_image,
            'gallery' => $this->gallery ?? [],
            'highlights' => $this->highlights ?? [],
            'status' => $this->status,
            'seoTitle' => $this->seo_title,
            'seoDescription' => $this->seo_description,
            'hotelCount' => $this->whenCounted('hotels'),
            'createdByAdminId' => $this->created_by_admin_id,
        ];
    }
}
