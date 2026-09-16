<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

/**
 * Spec section 9 — public, unauthenticated front-site endpoints.
 * Published-only, no scope/permission checks (there's no admin behind
 * this request), and 404 for anything not published so a draft/archived
 * location can't be discovered by guessing its slug.
 */
class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Location::withCount(['hotels' => fn ($query) => $query->where('status', 'published')])
            ->where('status', 'published')
            ->orderBy('name')
            ->get();

        return response()->json(['locations' => LocationResource::collection($locations)]);
    }

    public function show(string $slug): JsonResponse
    {
        $location = Location::withCount(['hotels' => fn ($query) => $query->where('status', 'published')])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json(['location' => new LocationResource($location)]);
    }
}
