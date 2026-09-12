<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login/logout for the `users` table (guest + manager accounts).
 *
 * Plain Laravel session auth — login sets a session cookie (regenerated
 * on login, invalidated on logout). In Postman: turn cookie jar on in
 * Settings, log in once, and every request after that carries the
 * cookie automatically.
 */
class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->json(['user' => Auth::guard('web')->user()]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }
}
