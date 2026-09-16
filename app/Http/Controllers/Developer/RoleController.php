<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "crud role for developer to add" — developer-only (the whole
 * `developer.php` route file is guarded by `auth:developer`, so there's
 * no separate permission check needed here the way admin routes need
 * `permission:<key>`).
 *
 * `scope` (`none`|`location`|`hotel`) is what makes a role's
 * locationIds/hotelIds requirement schema-driven — see Role's
 * docblock and App\Services\AccessService. A developer picking
 * `location` or `hotel` here is what gives a brand-new role real
 * scoping behavior, no code change required.
 */
class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['roles' => RoleResource::collection(Role::orderBy('name')->get())]);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json(['role' => new RoleResource($role)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'description' => ['nullable', 'string'],
            'isGlobal' => ['sometimes', 'boolean'],
            'scope' => ['sometimes', Rule::in(['none', 'location', 'hotel'])],
        ]);

        $role = Role::create($this->mapCamel($data));

        return response()->json(['role' => new RoleResource($role)], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'description' => ['nullable', 'string'],
            'isGlobal' => ['sometimes', 'boolean'],
            'scope' => ['sometimes', Rule::in(['none', 'location', 'hotel'])],
        ]);

        $role->update($this->mapCamel($data));

        return response()->json(['role' => new RoleResource($role)]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->packages()->exists()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Cannot delete a role that is attached to a package. Remove it from every package first.',
                'error' => 'Bad Request',
            ], 400);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapCamel(array $data): array
    {
        if (array_key_exists('isGlobal', $data)) {
            $data['is_global'] = $data['isGlobal'];
            unset($data['isGlobal']);
        }

        return $data;
    }
}
