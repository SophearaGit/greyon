<?php

namespace App\Http\Controllers\Developer\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\Auth\LoginRequest;
use App\Http\Resources\DeveloperResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->json(['developer' => new DeveloperResource(Auth::guard('developer')->user())]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('developer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(['developer' => new DeveloperResource(Auth::guard('developer')->user())]);
    }
}
