<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Resources\RatePlanResource;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Spec section 8 "Rate plans" — perm `rates`
 * (`permission:rates` on every route in routes/admin.php). Same shape
 * as RoomTypeController one level down: scope via `canAccessHotel`
 * against the rate plan's room type's `hotel_id` (not a direct column
 * on this table — always `loadMissing('roomType')` before checking),
 * `show`/`update` 404 not 403 out of scope (don't leak existence).
 *
 * store()/destroy() authorization: same 2026-09-15 policy as every
 * other content resource (see LocationController's docblock for the
 * full reasoning) — a developer always can; an admin can iff their
 * effective packages grant `rates`, already enforced by the route's
 * `permission:rates` middleware before store() runs, so no further
 * scope check is added here. destroy() stays global-seats-only.
 */
class RatePlanController extends Controller
{
    use AuthorizesAdminPanel;

    public function __construct(private readonly AccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $ratePlans = RatePlan::with('roomType')
            ->get()
            ->filter(fn (RatePlan $ratePlan) => $this->adminCanAccessHotel($request, $ratePlan->roomType->hotel_id))
            ->values();

        return response()->json(['ratePlans' => RatePlanResource::collection($ratePlans)]);
    }

    public function show(Request $request, RatePlan $ratePlan): JsonResponse
    {
        $this->authorizeScope($request, $ratePlan);

        return response()->json(['ratePlan' => new RatePlanResource($ratePlan->load('roomType'))]);
    }

    public function store(Request $request): JsonResponse
    {
        $ratePlan = RatePlan::create($this->validated($request));

        // Re-fetch so DB column defaults not present in the request
        // (e.g. status, taxPercent, serviceFeePercent) are reflected
        // in the response — Eloquent's create() doesn't otherwise pick
        // those up on the in-memory instance. Found while building
        // this controller; the same fix was applied to Location/Hotel/
        // RoomType's store() at the same time (2026-09-15).
        return response()->json(['ratePlan' => new RatePlanResource($ratePlan->refresh()->load('roomType'))], 201);
    }

    public function update(Request $request, RatePlan $ratePlan): JsonResponse
    {
        $this->authorizeScope($request, $ratePlan);

        $ratePlan->update($this->validated($request, $ratePlan));

        return response()->json(['ratePlan' => new RatePlanResource($ratePlan->load('roomType'))]);
    }

    public function destroy(Request $request, RatePlan $ratePlan): JsonResponse
    {
        $this->assertGlobalSeat($request);

        $ratePlan->delete();

        return response()->json(['message' => 'Rate plan deleted.']);
    }

    private function authorizeScope(Request $request, RatePlan $ratePlan): void
    {
        $ratePlan->loadMissing('roomType');

        if (! $this->adminCanAccessHotel($request, $ratePlan->roomType->hotel_id)) {
            throw new HttpException(404, 'Not found.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?RatePlan $ratePlan = null): array
    {
        $data = $request->validate([
            'roomTypeId' => [$ratePlan ? 'sometimes' : 'required', 'integer', Rule::exists(RoomType::class, 'id')],
            'name' => [$ratePlan ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mealBenefit' => ['nullable', 'string', 'max:255'],
            'cancellationPolicy' => ['nullable', 'string'],
            'basePrice' => [$ratePlan ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'taxPercent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'serviceFeePercent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
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
            'roomTypeId' => 'room_type_id',
            'mealBenefit' => 'meal_benefit',
            'cancellationPolicy' => 'cancellation_policy',
            'basePrice' => 'base_price',
            'taxPercent' => 'tax_percent',
            'serviceFeePercent' => 'service_fee_percent',
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
