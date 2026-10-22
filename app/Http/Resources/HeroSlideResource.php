<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a hero slide (GET /hero-slides).
 *
 * @mixin \App\Models\HeroSlide
 */
class HeroSlideResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'image' => $this->imageUrl(),
            'ctaLabel' => $this->cta_label,
            'ctaUrl' => $this->cta_url,
            'sortOrder' => $this->sort_order,
        ];
    }
}
