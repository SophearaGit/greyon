<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Home-page hero slide, managed from the admin panel.
 */
class HeroSlide extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'cta_label',
        'cta_url',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Active and inside its (optional) date window right now.
     *
     * @param  Builder<HeroSlide>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /**
     * @param  Builder<HeroSlide>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * `image` as an absolute URL (stored value may be a public-disk path or a URL).
     */
    public function imageUrl(): ?string
    {
        $image = $this->image;
        if ($image === null || $image === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        $url = Storage::disk('public')->url(ltrim($image, '/'));
        if (! str_starts_with($url, 'http')) {
            $url = rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
        }

        return $url;
    }
}
