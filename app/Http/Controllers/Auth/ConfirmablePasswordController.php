<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * "Confirm password" — re-checks the current user's password before a
 * sensitive action, without logging them out. Useful for a future
 * "change email" / "delete account" style endpoint.
 */
class ConfirmablePasswordController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'confirmed' => $request->session()->has('auth.password_confirmed_at'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->input('password'),
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return response()->json(['message' => 'Password confirmed.']);
    }
}
