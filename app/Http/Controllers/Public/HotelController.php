<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Spec section 9 — public, unauthenticated front-site endpoints.
 * Published-only; also excludes hotels under a location that isn't
 * itself published, and `locationSlug` lets the front site filter a
 * destination page's hotel list without needing ids.
 */
class HotelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $hotels = Hotel::with('location')
            ->where('status', 'published')
            ->whereHas('location', fn ($query) => $query->where('status', 'published'))
            ->when($request->query('locationSlug'), function ($query, $locationSlug) {
                $query->whereHas('location', fn ($q) => $q->where('slug', $locationSlug));
            })
            ->when($request->boolean('featured'), fn ($query) => $query->where('featured', true))
            ->orderBy('name')
            ->get();

        return response()->json(['hotels' => HotelResource::collection($hotels)]);
    }

    public function show(string $slug): JsonResponse
    {
        $hotel = Hotel::with('location')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereHas('location', fn ($query) => $query->where('status', 'published'))
            ->firstOrFail();

        return response()->json(['hotel' => new HotelResource($hotel)]);
    }
}
