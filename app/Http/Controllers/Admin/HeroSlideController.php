<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminHeroSlideResource;
use App\Models\HeroSlide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Home-page hero slides - perm `settings`. Images come from the
 * existing upload flow (POST /admin/uploads, folder "hero-slides"):
 * send its `path` (or a full https URL) as `image`.
 */
class HeroSlideController extends Controller
{
    /** Internal path (/hotels, /hotels/greyon-pp?x=1) or an http(s) URL. */
    private const CTA_URL_PATTERN = '#^(/(?!/)[^\s]*|https?://[^\s/]+[^\s]*)$#i';

    /** Public-disk path (no traversal, no leading //) or an http(s) URL. */
    private const IMAGE_PATTERN = '#^(https?://[^\s/]+[^\s]*|/?(?!/)(?!.*\.\.)[A-Za-z0-9._/-]+)$#i';

    public function index(): JsonResponse
    {
        return response()->json([
            'heroSlides' => AdminHeroSlideResource::collection(HeroSlide::query()->ordered()->get()),
        ]);
    }

    public function show(HeroSlide $heroSlide): JsonResponse
    {
        return response()->json(['heroSlide' => new AdminHeroSlideResource($heroSlide)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        // New slides go to the end unless a position is given.
        $data['sort_order'] ??= HeroSlide::query()->exists() ? ((int) HeroSlide::max('sort_order')) + 1 : 0;

        $slide = HeroSlide::create($data);

        return response()->json(['heroSlide' => new AdminHeroSlideResource($slide->refresh())], 201);
    }

    public function update(Request $request, HeroSlide $heroSlide): JsonResponse
    {
        $heroSlide->update($this->validated($request, $heroSlide));

        return response()->json(['heroSlide' => new AdminHeroSlideResource($heroSlide->refresh())]);
    }

    public function destroy(HeroSlide $heroSlide): JsonResponse
    {
        $heroSlide->delete();

        return response()->json(['message' => 'Hero slide deleted.']);
    }

    /**
     * POST { ids: [3, 1, 2] } - sets sort_order to each id's position (0-based).
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', Rule::exists('hero_slides', 'id')],
        ], [
            'ids.required' => 'Please send the slide ids in their new order.',
            'ids.array' => 'The slide order must be a list of slide ids.',
            'ids.min' => 'Please send at least one slide id.',
            'ids.*.integer' => 'Each slide id must be a number.',
            'ids.*.distinct' => 'Each slide can only appear once in the new order.',
            'ids.*.exists' => 'One of the slides no longer exists. Please refresh and try again.',
        ]);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['ids']) as $position => $id) {
                HeroSlide::whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return response()->json([
            'heroSlides' => AdminHeroSlideResource::collection(HeroSlide::query()->ordered()->get()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?HeroSlide $slide = null): array
    {
        $required = $slide ? 'sometimes' : 'required';

        $data = $request->validate([
            'title' => [$required, 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:240'],
            'image' => [$required, 'string', 'max:2048', 'regex:'.self::IMAGE_PATTERN],
            'ctaLabel' => ['nullable', 'string', 'max:60'],
            'ctaUrl' => ['nullable', 'string', 'max:2048', 'regex:'.self::CTA_URL_PATTERN],
            'sortOrder' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'isActive' => ['sometimes', 'boolean'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date'],
        ], [
            'title.required' => 'Please give the slide a title.',
            'title.max' => 'The title can be at most 120 characters.',
            'subtitle.max' => 'The subtitle can be at most 240 characters.',
            'image.required' => 'Please upload an image for the slide.',
            'image.regex' => 'The image must be an uploaded file path or a full https:// link.',
            'ctaLabel.max' => 'The button text can be at most 60 characters.',
            'ctaUrl.regex' => 'The button link must start with / (like /hotels) or be a full https:// link.',
            'sortOrder.integer' => 'The position must be a whole number.',
            'sortOrder.min' => 'The position cannot be negative.',
            'isActive.boolean' => 'Active must be true or false.',
            'startsAt.date' => 'The start date is not a valid date.',
            'endsAt.date' => 'The end date is not a valid date.',
            '*.string' => 'This field must be text.',
        ]);

        // A link needs button text (checked against the stored values on PATCH).
        $ctaUrl = array_key_exists('ctaUrl', $data) ? $data['ctaUrl'] : $slide?->cta_url;
        $ctaLabel = array_key_exists('ctaLabel', $data) ? $data['ctaLabel'] : $slide?->cta_label;
        if (filled($ctaUrl) && blank($ctaLabel)) {
            throw ValidationException::withMessages([
                'ctaLabel' => 'Please add button text for the link.',
            ]);
        }
        $startsAt = array_key_exists('startsAt', $data) ? $data['startsAt'] : $slide?->starts_at;
        $endsAt = array_key_exists('endsAt', $data) ? $data['endsAt'] : $slide?->ends_at;
        if ($startsAt && $endsAt && Carbon::parse($endsAt)->lt(Carbon::parse($startsAt))) {
            throw ValidationException::withMessages([
                'endsAt' => 'The end date must be after the start date.',
            ]);
        }

        $map = [
            'title' => 'title',
            'subtitle' => 'subtitle',
            'image' => 'image',
            'ctaLabel' => 'cta_label',
            'ctaUrl' => 'cta_url',
            'sortOrder' => 'sort_order',
            'isActive' => 'is_active',
            'startsAt' => 'starts_at',
            'endsAt' => 'ends_at',
        ];

        $out = [];
        foreach ($map as $camel => $snake) {
            if (array_key_exists($camel, $data)) {
                $out[$snake] = $data[$camel];
            }
        }

        if (isset($out['image']) && ! preg_match('#^https?://#i', $out['image'])) {
            $out['image'] = ltrim($out['image'], '/');
        }

        return $out;
    }
}
