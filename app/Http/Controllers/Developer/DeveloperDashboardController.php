<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeveloperResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeveloperDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['developer' => new DeveloperResource($request->user())]);
    }
}
