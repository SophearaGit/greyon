<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\NewsResource;
use App\Http\Resources\RatePlanResource;
use App\Http\Resources\RoomTypeResource;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\News;
use App\Models\RatePlan;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;

/**
 * One-shot published catalog for the Quasar SPA hydrate.
 * Locations + hotels that are published (hotel also requires published location);
 * room types / rate plans only when their parent chain is published;
 * published news articles included as `news`.
 *
 * Public payloads never include `data:` image URIs (admin local drops). Those
 * bloat JSON and have broken /catalog on production when a room gallery
 * stored a multi-hundred-KB base64 blob.
 */
class CatalogController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $locations = Location::withCount([
            'hotels' => fn ($query) => $query->where('status', 'published'),
        ])
            ->where('status', 'published')
            ->orderBy('name')
            ->get()
            ->each(fn (Location $location) => $this->scrubPublicMedia($location, ['hero_image'], ['gallery']));

        $hotels = Hotel::with('location')
            ->where('status', 'published')
            ->whereHas('location', fn ($query) => $query->where('status', 'published'))
            ->orderBy('name')
            ->get()
            ->each(fn (Hotel $hotel) => $this->scrubPublicMedia($hotel, ['hero_image'], ['gallery']));

        $hotelIds = $hotels->pluck('id');

        $roomTypes = RoomType::query()
            ->where('status', 'published')
            ->whereIn('hotel_id', $hotelIds)
            ->orderBy('name')
            ->get()
            ->each(fn (RoomType $roomType) => $this->scrubPublicMedia($roomType, [], ['images']));

        $roomTypeIds = $roomTypes->pluck('id');

        $ratePlans = RatePlan::query()
            ->where('status', 'published')
            ->whereIn('room_type_id', $roomTypeIds)
            ->orderBy('name')
            ->get();

        $news = News::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->each(fn (News $article) => $this->scrubPublicMedia($article, ['cover_image'], []));

        return response()->json([
            'locations' => LocationResource::collection($locations)->resolve(),
            'hotels' => HotelResource::collection($hotels)->resolve(),
            'roomTypes' => RoomTypeResource::collection($roomTypes)->resolve(),
            'ratePlans' => RatePlanResource::collection($ratePlans)->resolve(),
            'news' => NewsResource::collection($news)->resolve(),
        ]);
    }

    /**
     * Drop inline `data:` URIs and absurdly long strings from public JSON.
     *
     * @param  list<string>  $stringAttrs
     * @param  list<string>  $arrayAttrs
     */
    private function scrubPublicMedia(object $model, array $stringAttrs, array $arrayAttrs): void
    {
        foreach ($stringAttrs as $attr) {
            $value = $model->{$attr} ?? null;
            if (! is_string($value) || $this->isPublicImageUrl($value)) {
                continue;
            }
            $model->setAttribute($attr, null);
        }

        foreach ($arrayAttrs as $attr) {
            $values = $model->{$attr} ?? [];
            if (! is_array($values)) {
                $model->setAttribute($attr, []);
                continue;
            }
            $model->setAttribute(
                $attr,
                array_values(array_filter($values, fn ($src) => is_string($src) && $this->isPublicImageUrl($src)))
            );
        }
    }

    private function isPublicImageUrl(string $src): bool
    {
        if ($src === '' || str_starts_with($src, 'data:')) {
            return false;
        }

        // Keep normal https URLs; reject accidental multi-KB blobs.
        return strlen($src) <= 2048;
    }
}
