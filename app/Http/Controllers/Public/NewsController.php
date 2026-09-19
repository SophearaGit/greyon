<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Http\JsonResponse;

/**
 * Published news for the marketing site.
 */
class NewsController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = News::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['news' => NewsResource::collection($articles)]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = News::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json(['news' => new NewsResource($article)]);
    }
}
