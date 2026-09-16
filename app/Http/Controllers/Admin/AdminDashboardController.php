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
        $admin = $request->user()->load(['packages.roles', 'packages.features']);

        return response()->json(['admin' => new AdminResource($admin)]);
    }
}
