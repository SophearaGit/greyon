<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\Feature;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "crud permissions for developer to add" (2026-09-16) — the doc
 * maker's sample: "package -> roles, package -> features, package ->
 * permissions, feature -> permissions." A permission is a fine-grained
 * capability one level under a Feature (module-level visibility) — see
 * App\Models\Permission's docblock and
 * App\Services\AccessService::hasPermission(). Body shape mirrors
 * FeatureController's: `featureKey` (a feature *key*, not id) is
 * optional — a permission doesn't strictly need a feature to exist and
 * be grantable, though in practice every seeded one has one.
 */
class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::with('feature')->orderBy('sort_order')->orderBy('label')->get();

        return response()->json(['permissions' => PermissionResource::collection($permissions)]);
    }

    public function show(Permission $permission): JsonResponse
    {
        return response()->json(['permission' => new PermissionResource($permission->load('feature'))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $permission = Permission::create($data);

        return response()->json(['permission' => new PermissionResource($permission->load('feature'))], 201);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $this->validated($request, $permission);

        $permission->update($data);

        return response()->json(['permission' => new PermissionResource($permission->load('feature'))]);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        if ($permission->packages()->exists()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Cannot delete a permission that is attached to a package. Remove it from every package first.',
                'error' => 'Bad Request',
            ], 400);
        }

        $permission->delete();

        return response()->json(['message' => 'Permission deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Permission $permission = null): array
    {
        $data = $request->validate([
            'key' => [$permission ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('permissions', 'key')->ignore($permission?->id)],
            'label' => [$permission ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'featureKey' => ['nullable', 'string', Rule::exists('features', 'key')],
            'sortOrder' => ['sometimes', 'integer'],
        ]);

        if (array_key_exists('featureKey', $data)) {
            $data['feature_id'] = $data['featureKey'] === null
                ? null
                : Feature::where('key', $data['featureKey'])->value('id');
            unset($data['featureKey']);
        }

        if (array_key_exists('sortOrder', $data)) {
            $data['sort_order'] = $data['sortOrder'];
            unset($data['sortOrder']);
        }

        return $data;
    }
}
