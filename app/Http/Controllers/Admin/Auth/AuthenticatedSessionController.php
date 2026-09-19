<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Resources\AdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user()->load(['packages.roles', 'packages.features', 'packages.permissions']);

        return response()->json(['admin' => new AdminResource($admin)]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function show(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user()->load(['packages.roles', 'packages.features', 'packages.permissions']);

        return response()->json(['admin' => new AdminResource($admin)]);
    }
}
