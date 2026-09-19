<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SiteSetting
 */
class SiteSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'siteName' => $this->site_name,
            'defaultTitle' => $this->default_title,
            'defaultDescription' => $this->default_description,
            'ogImage' => $this->og_image,
            'heroSlides' => collect($this->hero_slides ?? [])
                ->filter(fn ($slide) => is_array($slide) && ! empty($slide['src']))
                ->values()
                ->map(fn ($slide) => [
                    'src' => (string) $slide['src'],
                    'alt' => (string) ($slide['alt'] ?? ''),
                ])
                ->all(),
            'analyticsId' => $this->analytics_id,
            'contactEmail' => $this->contact_email,
            'contactPhone' => $this->contact_phone,
            'siteUrl' => $this->site_url,
            'paymentEnabled' => (bool) $this->payment_enabled,
            'paymentNote' => $this->payment_note,
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
