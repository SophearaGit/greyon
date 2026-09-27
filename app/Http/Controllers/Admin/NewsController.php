<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Resources\NewsResource;
use App\Models\Location;
use App\Models\News;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Spec "News" — perm `news`. Optionally scoped to a destination.
 */
class NewsController extends Controller
{
    use AuthorizesAdminPanel;

    public function __construct(private readonly AccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $locationId = $request->query('locationId');

        $articles = News::query()
            ->with('location')
            ->when($locationId !== null && $locationId !== '', function ($q) use ($locationId) {
                if ($locationId === 'none') {
                    $q->whereNull('location_id');
                } else {
                    $q->where('location_id', (int) $locationId);
                }
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (News $article) => $this->canSee($request, $article))
            ->values();

        return response()->json(['news' => NewsResource::collection($articles)]);
    }

    public function show(Request $request, News $news): JsonResponse
    {
        $this->authorizeSee($request, $news);

        return response()->json(['news' => new NewsResource($news->load('location'))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $this->assertLocationScope($request, $data['location_id'] ?? null);

        $article = News::create($data);

        return response()->json([
            'news' => new NewsResource($article->refresh()->load('location')),
        ], 201);
    }

    public function update(Request $request, News $news): JsonResponse
    {
        $this->authorizeSee($request, $news);

        $data = $this->validated($request, $news);
        $locationId = array_key_exists('location_id', $data)
            ? $data['location_id']
            : $news->location_id;
        $this->assertLocationScope($request, $locationId);

        $news->update($data);

        return response()->json([
            'news' => new NewsResource($news->refresh()->load('location')),
        ]);
    }

    public function destroy(Request $request, News $news): JsonResponse
    {
        $this->authorizeSee($request, $news);
        $this->assertGlobalSeat($request);

        $news->delete();

        return response()->json(['message' => 'News article deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?News $news = null): array
    {
        $data = $request->validate([
            'locationId' => ['nullable', 'integer', Rule::exists(Location::class, 'id')],
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
            'locationId' => 'location_id',
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

    private function authorizeSee(Request $request, News $news): void
    {
        if (! $this->canSee($request, $news)) {
            throw new HttpException(404, 'Not found.');
        }
    }

    private function canSee(Request $request, News $news): bool
    {
        if ($this->isDeveloper($request)) {
            return true;
        }

        $admin = $this->actingAdmin($request);
        if (! $admin) {
            return false;
        }

        if ($this->access->isGlobal($admin)) {
            return true;
        }

        // Site-wide stories (no destination) — global seats only.
        if ($news->location_id === null) {
            return false;
        }

        return $this->access->canAccessLocation($admin, $news->location_id);
    }

    private function assertLocationScope(Request $request, mixed $locationId): void
    {
        if ($locationId === null) {
            // Creating/editing site-wide news requires a global seat.
            $this->assertGlobalSeat($request);

            return;
        }

        if (! $this->adminCanAccessLocation($request, $locationId)) {
            throw new HttpException(404, 'Not found.');
        }
    }
}
