<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user('admin');
        if (! $admin) {
            // Developer sessions may hit this via auth:admin,developer —
            // they have no admin packages to serialize.
            return response()->json(['admin' => null]);
        }

        $admin->load(['packages.roles', 'packages.features', 'packages.permissions']);

        return response()->json(['admin' => new AdminResource($admin)]);
    }
}
