<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Resources\RateCalendarResource;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Spec "Rate calendar" under perm `rates`. Scope via rate plan → room → hotel.
 */
class RateCalendarController extends Controller
{
    use AuthorizesAdminPanel;

    public function __construct(private readonly AccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $query = RateCalendar::with('ratePlan.roomType')
            ->when($request->query('ratePlanId'), fn ($q, $id) => $q->where('rate_plan_id', $id))
            ->when($request->query('from'), fn ($q, $from) => $q->where('date', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->where('date', '<=', $to))
            ->orderBy('date');

        $rows = $query->get()
            ->filter(fn (RateCalendar $row) => $this->adminCanAccessHotel($request, $row->ratePlan->roomType->hotel_id))
            ->values();

        return response()->json(['rateCalendars' => RateCalendarResource::collection($rows)]);
    }

    public function show(Request $request, RateCalendar $rateCalendar): JsonResponse
    {
        $this->authorizeScope($request, $rateCalendar);

        return response()->json(['rateCalendar' => new RateCalendarResource($rateCalendar)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $this->assertRatePlanInScope($request, $data['rate_plan_id']);

        $rateCalendar = RateCalendar::create($data);

        return response()->json(['rateCalendar' => new RateCalendarResource($rateCalendar->refresh())], 201);
    }

    public function update(Request $request, RateCalendar $rateCalendar): JsonResponse
    {
        $this->authorizeScope($request, $rateCalendar);

        $data = $this->validated($request, $rateCalendar);
        if (isset($data['rate_plan_id'])) {
            $this->assertRatePlanInScope($request, $data['rate_plan_id']);
        }

        $rateCalendar->update($data);

        return response()->json(['rateCalendar' => new RateCalendarResource($rateCalendar->refresh())]);
    }

    public function destroy(Request $request, RateCalendar $rateCalendar): JsonResponse
    {
        $this->authorizeScope($request, $rateCalendar);
        $this->assertGlobalSeat($request);

        $rateCalendar->delete();

        return response()->json(['message' => 'Rate calendar deleted.']);
    }

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ratePlanId' => ['required', 'integer', Rule::exists(RatePlan::class, 'id')],
            'date' => ['required', 'date_format:Y-m-d'],
            'price' => ['required', 'numeric', 'min:0'],
            'minStay' => ['nullable', 'integer', 'min:1'],
            'maxStay' => ['nullable', 'integer', 'min:1'],
        ]);

        $mapped = $this->mapCamel($data);
        $this->assertRatePlanInScope($request, $mapped['rate_plan_id']);

        $rateCalendar = RateCalendar::updateOrCreate(
            [
                'rate_plan_id' => $mapped['rate_plan_id'],
                'date' => $mapped['date'],
            ],
            [
                'price' => $mapped['price'],
                'min_stay' => $mapped['min_stay'] ?? null,
                'max_stay' => $mapped['max_stay'] ?? null,
            ],
        );

        return response()->json(['rateCalendar' => new RateCalendarResource($rateCalendar->refresh())]);
    }

    private function authorizeScope(Request $request, RateCalendar $rateCalendar): void
    {
        $rateCalendar->loadMissing('ratePlan.roomType');

        if (! $this->adminCanAccessHotel($request, $rateCalendar->ratePlan->roomType->hotel_id)) {
            throw new HttpException(404, 'Not found.');
        }
    }

    private function assertRatePlanInScope(Request $request, int $ratePlanId): void
    {
        $ratePlan = RatePlan::with('roomType')->findOrFail($ratePlanId);

        if (! $this->adminCanAccessHotel($request, $ratePlan->roomType->hotel_id)) {
            throw new HttpException(404, 'Not found.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?RateCalendar $rateCalendar = null): array
    {
        $data = $request->validate([
            'ratePlanId' => [$rateCalendar ? 'sometimes' : 'required', 'integer', Rule::exists(RatePlan::class, 'id')],
            'date' => [$rateCalendar ? 'sometimes' : 'required', 'date_format:Y-m-d'],
            'price' => [$rateCalendar ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'minStay' => ['nullable', 'integer', 'min:1'],
            'maxStay' => ['nullable', 'integer', 'min:1'],
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
            'ratePlanId' => 'rate_plan_id',
            'minStay' => 'min_stay',
            'maxStay' => 'max_stay',
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
