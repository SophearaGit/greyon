<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Placeholder — booking create/view/cancel lands here in a later pass.
 * Any authenticated user in the `users` table (guest or manager) can
 * reach these; ownership checks (a guest only sees their own bookings)
 * are added alongside the real implementation.
 */
class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Booking workflow — not implemented yet.',
            'user' => $request->user()->only('id', 'name', 'role'),
            'bookings' => [],
        ]);
    }
}
