<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\HeroSlideResource;
use App\Models\HeroSlide;
use Illuminate\Http\JsonResponse;

/**
 * Home-page hero slides: active, inside their date window, by sort_order.
 */
class HeroSlideController extends Controller
{
    public function index(): JsonResponse
    {
        $slides = HeroSlide::query()->visible()->ordered()->get();

        return response()->json(['heroSlides' => HeroSlideResource::collection($slides)]);
    }
}
