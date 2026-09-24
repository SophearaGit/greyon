<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Resources\HotelResource;
use App\Models\Admin;
use App\Models\Hotel;
use App\Models\Location;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Spec section 8 "Hotels" — perm `hotels`
 * (`permission:hotels` on every route in routes/admin.php).
 *
 * Scope (spec section 5) via `canAccessHotel`, entirely schema-driven
 * via App\Models\Role::scope (see AccessService): global seats see
 * everything, a hotel-scoped role sees hotels in its `hotelIds`, a
 * location-scoped role sees hotels under locations in its
 * `locationIds`. Out-of-scope → 404, same "don't leak existence" rule
 * as LocationController.
 *
 * store() authorization (2026-09-15 policy — see LocationController's
 * docblock for the same change made at the same time): a developer can
 * always create (bypasses this controller's `permission:` gate
 * entirely). An admin can create iff their effective packages grant
 * the `hotels` feature — already enforced by the route's
 * `permission:hotels` middleware before this method runs, so store()
 * adds no further scope check on top of it (this replaces an earlier
 * global-seat-or-location-manager-only restriction that used
 * `isLocationManagerOf()`).
 *
 * destroy() is still restricted to global seats — deleting a hotel
 * wasn't part of this change, only create moved to feature-only.
 *
 * store() quantity cap (2026-09-16): an admin's packages can also cap
 * how many hotels *a single location* is allowed to hold —
 * App\Services\AccessService::effectiveLimit() against the
 * `hotels_per_location` resource key (App\Models\PackageLimit), paired
 * with the `hotels` feature. Unlike LocationController's `locations`
 * cap, this one is **not** per-creating-admin: it counts every hotel
 * already under the target `locationId`, regardless of who created
 * them, because the constraint is a property of the location slot
 * ("this destination gets one hotel"), not of the admin doing the
 * creating. Round 12 (2026-09-22) capped every seeded package by
 * default — see `database/seeders/PackageSeeder.php`'s docblock: the
 * `Admin` package and all 3 `Manager · <City>` packages are all capped
 * at 3 (unlike `locations`, where only `Admin` can create at all — see
 * LocationController's docblock). A developer building a brand-new
 * package can still leave this uncapped by simply omitting a
 * `hotels_per_location` limit row.
 */
class HotelController extends Controller
{
    use AuthorizesAdminPanel;

    public function __construct(private readonly AccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $hotels = Hotel::with('location')
            ->get()
            ->filter(fn (Hotel $hotel) => $this->adminCanAccessHotel($request, $hotel->id))
            ->values();

        return response()->json(['hotels' => HotelResource::collection($hotels)]);
    }

    public function show(Request $request, Hotel $hotel): JsonResponse
    {
        $this->authorizeScope($request, $hotel);

        return response()->json(['hotel' => new HotelResource($hotel->load('location'))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $admin = $this->actingAdmin($request);
        if ($admin) {
            $this->assertWithinCreateLimit($admin, $data['location_id']);
        }

        $hotel = Hotel::create($data);

        // Re-fetch so DB column defaults not present in $data (e.g.
        // status) are reflected in the response — Eloquent's create()
        // doesn't otherwise pick those up on the in-memory instance.
        return response()->json(['hotel' => new HotelResource($hotel->refresh()->load('location'))], 201);
    }

    public function update(Request $request, Hotel $hotel): JsonResponse
    {
        $this->authorizeScope($request, $hotel);

        $hotel->update($this->validated($request, $hotel));

        return response()->json(['hotel' => new HotelResource($hotel->load('location'))]);
    }

    public function destroy(Request $request, Hotel $hotel): JsonResponse
    {
        $this->assertGlobalSeat($request);

        $hotel->delete();

        return response()->json(['message' => 'Hotel deleted.']);
    }

    private function authorizeScope(Request $request, Hotel $hotel): void
    {
        if (! $this->adminCanAccessHotel($request, $hotel->id)) {
            throw new HttpException(404, 'Not found.');
        }
    }

    /**
     * The `hotels_per_location` package-limit check (see class
     * docblock) — how many hotels the *target location* already has,
     * not how many this admin has personally created.
     */
    private function assertWithinCreateLimit(Admin $admin, int $locationId): void
    {
        $limit = $this->access->effectiveLimit($admin, 'hotels', 'hotels_per_location');

        if ($limit === null) {
            return;
        }

        $used = Hotel::where('location_id', $locationId)->count();

        if ($used >= $limit) {
            throw new HttpException(403, "This location already has the maximum {$limit} hotel(s) allowed by your package.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Hotel $hotel = null): array
    {
        $data = $request->validate([
            'locationId' => [$hotel ? 'sometimes' : 'required', 'integer', Rule::exists(Location::class, 'id')],
            'name' => [$hotel ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [
                $hotel ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('hotels', 'slug')->ignore($hotel?->id),
            ],
            'shortDescription' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'coordinates' => ['sometimes', 'array'],
            'coordinates.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'coordinates.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'mapEmbedUrl' => ['nullable', 'string', 'max:4096'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'heroImage' => ['nullable', 'string', 'max:2048'],
            'gallery' => ['array'],
            'gallery.*' => ['string'],
            'amenities' => ['array'],
            'amenities.*' => ['string'],
            'policies' => ['array'],
            'policies.*' => ['string'],
            'checkInTime' => ['nullable', 'string', 'max:20'],
            'checkOutTime' => ['nullable', 'string', 'max:20'],
            'featured' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string'],
        ]);

        if (array_key_exists('coordinates', $data)) {
            $data['lat'] = $data['coordinates']['lat'] ?? null;
            $data['lng'] = $data['coordinates']['lng'] ?? null;
            unset($data['coordinates']);
        }

        return $this->mapCamel($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapCamel(array $data): array
    {
        $map = [
            'locationId' => 'location_id',
            'shortDescription' => 'short_description',
            'heroImage' => 'hero_image',
            'mapEmbedUrl' => 'map_embed_url',
            'checkInTime' => 'check_in_time',
            'checkOutTime' => 'check_out_time',
            'seoTitle' => 'seo_title',
            'seoDescription' => 'seo_description',
        ];

        foreach ($map as $camel => $snake) {
            if (array_key_exists($camel, $data)) {
                $data[$snake] = $data[$camel];
                unset($data[$camel]);
            }
        }

        return $data;
    }
}
