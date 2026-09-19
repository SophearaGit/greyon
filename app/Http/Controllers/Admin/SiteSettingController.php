<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteSettingResource;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Spec "Settings" — perm `settings`. Singleton site SEO / contact / payment.
 */
class SiteSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['settings' => new SiteSettingResource(SiteSetting::current())]);
    }

    public function update(Request $request): JsonResponse
    {
        $settings = SiteSetting::current();
        $settings->update($this->validated($request));

        return response()->json(['settings' => new SiteSettingResource($settings->refresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'siteName' => ['sometimes', 'string', 'max:255'],
            'defaultTitle' => ['sometimes', 'string', 'max:255'],
            'defaultDescription' => ['sometimes', 'string'],
            'ogImage' => ['sometimes', 'string', 'max:2048'],
            'heroSlides' => ['sometimes', 'array', 'max:12'],
            'heroSlides.*.src' => ['required_with:heroSlides', 'string', 'max:2048'],
            'heroSlides.*.alt' => ['nullable', 'string', 'max:255'],
            'analyticsId' => ['sometimes', 'string', 'max:255'],
            'contactEmail' => ['sometimes', 'email', 'max:255'],
            'contactPhone' => ['sometimes', 'string', 'max:255'],
            'siteUrl' => ['sometimes', 'string', 'max:255'],
            'paymentEnabled' => ['sometimes', 'boolean'],
            'paymentNote' => ['sometimes', 'string'],
        ]);

        return $this->mapCamel($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapCamel(array $data): array
    {
        $map = [
            'siteName' => 'site_name',
            'defaultTitle' => 'default_title',
            'defaultDescription' => 'default_description',
            'ogImage' => 'og_image',
            'heroSlides' => 'hero_slides',
            'analyticsId' => 'analytics_id',
            'contactEmail' => 'contact_email',
            'contactPhone' => 'contact_phone',
            'siteUrl' => 'site_url',
            'paymentEnabled' => 'payment_enabled',
            'paymentNote' => 'payment_note',
        ];

        foreach ($map as $camel => $snake) {
            if (array_key_exists($camel, $data)) {
                $data[$snake] = $data[$camel];
                unset($data[$camel]);
            }
        }

        if (isset($data['hero_slides']) && is_array($data['hero_slides'])) {
            $data['hero_slides'] = array_values(array_map(
                static fn ($slide) => [
                    'src' => (string) ($slide['src'] ?? ''),
                    'alt' => (string) ($slide['alt'] ?? ''),
                ],
                array_filter(
                    $data['hero_slides'],
                    static fn ($slide) => is_array($slide) && ! empty($slide['src'])
                )
            ));
        }

        return $data;
    }
}
