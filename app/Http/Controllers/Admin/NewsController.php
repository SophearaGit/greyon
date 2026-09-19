<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\News;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Spec "News" — perm `news`. Global CMS content (no hotel scope).
 */
class NewsController extends Controller
{
    public function __construct(private readonly AccessService $access) {}

    public function index(): JsonResponse
    {
        $articles = News::query()->orderByDesc('published_at')->orderByDesc('id')->get();

        return response()->json(['news' => NewsResource::collection($articles)]);
    }

    public function show(News $news): JsonResponse
    {
        return response()->json(['news' => new NewsResource($news)]);
    }

    public function store(Request $request): JsonResponse
    {
        $article = News::create($this->validated($request));

        return response()->json(['news' => new NewsResource($article->refresh())], 201);
    }

    public function update(Request $request, News $news): JsonResponse
    {
        $news->update($this->validated($request, $news));

        return response()->json(['news' => new NewsResource($news->refresh())]);
    }

    public function destroy(Request $request, News $news): JsonResponse
    {
        if (! $this->access->isGlobal($request->user('admin'))) {
            throw new HttpException(403, 'Only a global seat can do this.');
        }

        $news->delete();

        return response()->json(['message' => 'News article deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?News $news = null): array
    {
        $data = $request->validate([
            'title' => [$news ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [$news ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('news', 'slug')->ignore($news?->id)],
            'coverImage' => [$news ? 'sometimes' : 'required', 'string', 'max:2048'],
            'excerpt' => [$news ? 'sometimes' : 'required', 'string'],
            'body' => [$news ? 'sometimes' : 'required', 'string'],
            'publishedAt' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string'],
        ]);

        return $this->mapCamel($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapCamel(array $data): array
    {
        $map = [
            'coverImage' => 'cover_image',
            'publishedAt' => 'published_at',
            'seoTitle' => 'seo_title',
            'seoDescription' => 'seo_description',
        ];

        foreach ($map as $camel => $snake) {
            if (array_key_exists($camel, $data)) {
                $data[$snake] = $data[$camel];
                unset($data[$camel]);
            }
        }

        return $data;
    }
}
