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
            ->get();

        $hotels = Hotel::with('location')
            ->where('status', 'published')
            ->whereHas('location', fn ($query) => $query->where('status', 'published'))
            ->orderBy('name')
            ->get();

        $hotelIds = $hotels->pluck('id');

        $roomTypes = RoomType::query()
            ->where('status', 'published')
            ->whereIn('hotel_id', $hotelIds)
            ->orderBy('name')
            ->get();

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
            ->get();

        return response()->json([
            'locations' => LocationResource::collection($locations),
            'hotels' => HotelResource::collection($hotels),
            'roomTypes' => RoomTypeResource::collection($roomTypes),
            'ratePlans' => RatePlanResource::collection($ratePlans),
            'news' => NewsResource::collection($news),
        ]);
    }
}
