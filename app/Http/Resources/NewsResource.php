<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\News
 */
class NewsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'coverImage' => $this->cover_image,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'publishedAt' => $this->published_at?->format('Y-m-d'),
            'status' => $this->status,
            'seoTitle' => $this->seo_title,
            'seoDescription' => $this->seo_description,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
