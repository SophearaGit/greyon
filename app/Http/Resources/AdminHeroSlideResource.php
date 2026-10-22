<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Admin shape of a hero slide: the public fields plus the raw stored
 * image value and scheduling/visibility fields.
 *
 * @mixin \App\Models\HeroSlide
 */
class AdminHeroSlideResource extends HeroSlideResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'imagePath' => $this->image,
            'isActive' => $this->is_active,
            'startsAt' => $this->starts_at?->toIso8601String(),
            'endsAt' => $this->ends_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
