<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Controllers\Concerns\ManagesCatalogItems;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Client requirements doc (2026-10-04), item 6 - "Services": a managed
 * list, selected the same way as Products (see HotelController's
 * `serviceIds`), replacing the old free-text service input. Perm
 * `services`.
 *
 * A flat, shared catalog (not scoped to one location/hotel), so there is
 * no canAccessX scope check: anyone holding `services` sees the full
 * catalog. store()/update() are feature-gated only (route middleware);
 * destroy() is global-seats-only, matching every other resource.
 * Deleting a service just drops it from hotels (cascadeOnDelete pivot).
 */
class ServiceController extends Controller
{
    use AuthorizesAdminPanel;
    use ManagesCatalogItems;

    public function __construct(private readonly AccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $services = $this->catalogIndexQuery($request, Service::class)->get();

        return response()->json(['services' => ServiceResource::collection($services)]);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json(['service' => new ServiceResource($service->loadCount('hotels'))]);
    }

    public function store(Request $request): JsonResponse
    {
        $service = Service::create($this->validated($request));

        // refresh() so DB defaults (status, sort_order) are in the response.
        return response()->json(['service' => new ServiceResource($service->refresh()->loadCount('hotels'))], 201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $service->update($this->validated($request, $service));

        return response()->json(['service' => new ServiceResource($service->refresh()->loadCount('hotels'))]);
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
        $data = $request->validate([
            'name' => [$service ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('services', 'slug')->ignore($service?->id),
            ],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sortOrder' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if ($service && array_key_exists('slug', $data) && blank($data['slug'])) {
            unset($data['slug']);
        }

        return $this->normalizeCatalogPayload($data, Service::class, $service);
    }
}
