<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Placeholder — hotel management (rooms, availability, rates, reports)
 * lands here in a later pass. Reachable only by a `users` row with
 * role = "manager" (see the `role` middleware).
 */
class ManagerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Welcome to the manager dashboard.',
            'manager' => $request->user()->only('id', 'name', 'role'),
        ]);
    }
}
