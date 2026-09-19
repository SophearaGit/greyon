<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaItemResource;
use App\Models\MediaItem;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Spec "Media" — perm `media`. URL-based library (upload storage TBD).
 */
class MediaItemController extends Controller
{
    public function __construct(private readonly AccessService $access) {}

    public function index(): JsonResponse
    {
        $items = MediaItem::query()->orderByDesc('created_at')->get();

        return response()->json(['media' => MediaItemResource::collection($items)]);
    }

    public function show(MediaItem $mediaItem): JsonResponse
    {
        return response()->json(['mediaItem' => new MediaItemResource($mediaItem)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['created_by_admin_id'] = $request->user('admin')->id;

        $item = MediaItem::create($data);

        return response()->json(['mediaItem' => new MediaItemResource($item->refresh())], 201);
    }

    public function update(Request $request, MediaItem $mediaItem): JsonResponse
    {
        $mediaItem->update($this->validated($request, $mediaItem));

        return response()->json(['mediaItem' => new MediaItemResource($mediaItem->refresh())]);
    }

    public function destroy(Request $request, MediaItem $mediaItem): JsonResponse
    {
        if (! $this->access->isGlobal($request->user('admin'))) {
            throw new HttpException(403, 'Only a global seat can do this.');
        }

        $mediaItem->delete();

        return response()->json(['message' => 'Media item deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?MediaItem $mediaItem = null): array
    {
        return $request->validate([
            'src' => [$mediaItem ? 'sometimes' : 'required', 'string', 'max:2048'],
            'alt' => ['sometimes', 'string', 'max:255'],
        ]);
    }
}
