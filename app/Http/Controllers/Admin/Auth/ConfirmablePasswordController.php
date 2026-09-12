<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
        if (! Auth::guard('admin')->validate([
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
