<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeatureResource;
use App\Models\Feature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "crud features for developer to add" — developer-only via the
 * `auth:developer` guard on the whole route group.
 */
class FeatureController extends Controller
{
    public function index(): JsonResponse
    {
        $features = Feature::with('permissions')->orderBy('category')->orderBy('sort_order')->orderBy('label')->get();

        return response()->json(['features' => FeatureResource::collection($features)]);
    }

    public function show(Feature $feature): JsonResponse
    {
        return response()->json(['feature' => new FeatureResource($feature->load('permissions'))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $feature = Feature::create($data);

        return response()->json(['feature' => new FeatureResource($feature)], 201);
    }

    public function update(Request $request, Feature $feature): JsonResponse
    {
        $data = $this->validated($request, $feature);

        $feature->update($data);

        return response()->json(['feature' => new FeatureResource($feature)]);
    }

    public function destroy(Feature $feature): JsonResponse
    {
        if ($feature->packages()->exists()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Cannot delete a feature that is attached to a package. Remove it from every package first.',
                'error' => 'Bad Request',
            ], 400);
        }

        if ($feature->permissions()->exists()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Cannot delete a feature that has permissions catalogued under it. Reassign or delete them first.',
                'error' => 'Bad Request',
            ], 400);
        }

        $feature->delete();

        return response()->json(['message' => 'Feature deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Feature $feature = null): array
    {
        $data = $request->validate([
            'key' => [$feature ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('features', 'key')->ignore($feature?->id)],
            'label' => [$feature ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', Rule::in(['admin', 'public'])],
            'sortOrder' => ['sometimes', 'integer'],
        ]);

        if (array_key_exists('sortOrder', $data)) {
            $data['sort_order'] = $data['sortOrder'];
            unset($data['sortOrder']);
        }

        return $data;
    }
}
