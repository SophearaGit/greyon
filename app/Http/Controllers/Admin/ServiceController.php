<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Client requirements doc (2026-10-04), item 6 — "Services": a
 * developer/admin-managed catalog a hotel picks from (see
 * HotelController's `serviceIds`), replacing the old free-text service
 * input. Perm `services` (`permission:services` on every route in
 * routes/admin.php).
 *
 * Unlike Locations/Hotels/RoomTypes/RatePlans, this resource is a
 * flat, **shared** catalog — not scoped to one location/hotel — so
 * there's no `canAccessX`/404-not-403 scope check here: any admin
 * holding the `services` feature can see and pick from the full
 * catalog, same as Features/Permissions/Roles are visible to whoever
 * can reach the package builder.
 *
 * store()/update() are feature-gated only (the route's
 * `permission:services` middleware already requires it) — same
 * 2026-09-15 policy used by Hotel/RoomType/RatePlan: a developer can
 * always manage the catalog, an admin can iff their packages grant
 * `services`. destroy() is global-seats-only, matching every other
 * resource's create/update-permissive-but-delete-restrictive
 * convention. Deleting a service does not check whether any hotel
 * still references it — see the `hotel_service` migration's docblock
 * for why that's deliberate (cascadeOnDelete, not restrictOnDelete).
 */
class ServiceController extends Controller
{
    use AuthorizesAdminPanel;

    public function __construct(private readonly AccessService $access) {}

    public function index(): JsonResponse
    {
        $services = Service::orderBy('name')->get();

        return response()->json(['services' => ServiceResource::collection($services)]);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json(['service' => new ServiceResource($service)]);
    }

    public function store(Request $request): JsonResponse
    {
        $service = Service::create($this->validated($request));

        // Re-fetch so DB column defaults not present in the request
        // (e.g. status) are reflected in the response — Eloquent's
        // create() doesn't otherwise pick those up on the in-memory
        // instance.
        return response()->json(['service' => new ServiceResource($service->refresh())], 201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $service->update($this->validated($request, $service));

        return response()->json(['service' => new ServiceResource($service)]);
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->assertGlobalSeat($request);

        $service->delete();

        return response()->json(['message' => 'Service deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Service $service = null): array
    {
        return $request->validate([
            'name' => [$service ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [
                $service ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('services', 'slug')->ignore($service?->id),
            ],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
        ]);
    }
}
