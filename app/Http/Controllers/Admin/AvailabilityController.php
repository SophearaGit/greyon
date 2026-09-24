<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Resources\AvailabilityResource;
use App\Models\Availability;
use App\Models\RoomType;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Spec "Availability" under perm `rates`. Scope via room type → hotel.
 */
class AvailabilityController extends Controller
{
    use AuthorizesAdminPanel;

    public function __construct(private readonly AccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $query = Availability::with('roomType')
            ->when($request->query('roomTypeId'), fn ($q, $id) => $q->where('room_type_id', $id))
            ->when($request->query('from'), fn ($q, $from) => $q->where('date', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->where('date', '<=', $to))
            ->orderBy('date');

        $rows = $query->get()
            ->filter(fn (Availability $row) => $this->adminCanAccessHotel($request, $row->roomType->hotel_id))
            ->values();

        return response()->json(['availabilities' => AvailabilityResource::collection($rows)]);
    }

    public function show(Request $request, Availability $availability): JsonResponse
    {
        $this->authorizeScope($request, $availability);

        return response()->json(['availability' => new AvailabilityResource($availability)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $this->assertRoomTypeInScope($request, $data['room_type_id']);

        $availability = Availability::create($data);

        return response()->json(['availability' => new AvailabilityResource($availability->refresh())], 201);
    }

    public function update(Request $request, Availability $availability): JsonResponse
    {
        $this->authorizeScope($request, $availability);

        $data = $this->validated($request, $availability);
        if (isset($data['room_type_id'])) {
            $this->assertRoomTypeInScope($request, $data['room_type_id']);
        }

        $availability->update($data);

        return response()->json(['availability' => new AvailabilityResource($availability->refresh())]);
    }

    public function destroy(Request $request, Availability $availability): JsonResponse
    {
        $this->authorizeScope($request, $availability);
        $this->assertGlobalSeat($request);

        $availability->delete();

        return response()->json(['message' => 'Availability deleted.']);
    }

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'roomTypeId' => ['required', 'integer', Rule::exists(RoomType::class, 'id')],
            'date' => ['required', 'date_format:Y-m-d'],
            'availableUnits' => ['required', 'integer', 'min:0'],
            'stopSell' => ['sometimes', 'boolean'],
        ]);

        $mapped = $this->mapCamel($data);
        $this->assertRoomTypeInScope($request, $mapped['room_type_id']);

        $availability = Availability::updateOrCreate(
            [
                'room_type_id' => $mapped['room_type_id'],
                'date' => $mapped['date'],
            ],
            [
                'available_units' => $mapped['available_units'],
                'stop_sell' => $mapped['stop_sell'] ?? false,
            ],
        );

        return response()->json(['availability' => new AvailabilityResource($availability->refresh())]);
    }

    private function authorizeScope(Request $request, Availability $availability): void
    {
        $availability->loadMissing('roomType');

        if (! $this->adminCanAccessHotel($request, $availability->roomType->hotel_id)) {
            throw new HttpException(404, 'Not found.');
        }
    }

    private function assertRoomTypeInScope(Request $request, int $roomTypeId): void
    {
        $roomType = RoomType::query()->findOrFail($roomTypeId);

        if (! $this->adminCanAccessHotel($request, $roomType->hotel_id)) {
            throw new HttpException(404, 'Not found.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Availability $availability = null): array
    {
        $data = $request->validate([
            'roomTypeId' => [$availability ? 'sometimes' : 'required', 'integer', Rule::exists(RoomType::class, 'id')],
            'date' => [$availability ? 'sometimes' : 'required', 'date_format:Y-m-d'],
            'availableUnits' => [$availability ? 'sometimes' : 'required', 'integer', 'min:0'],
            'stopSell' => ['sometimes', 'boolean'],
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
            'availableUnits' => 'available_units',
            'stopSell' => 'stop_sell',
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
