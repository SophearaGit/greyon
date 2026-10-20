<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Shared helpers for the flat Services / Products catalogs
 * (Admin\ServiceController, Admin\ProductController).
 */
trait ManagesCatalogItems
{
    /**
     * Admin list: optional `status` and `q` (name search) filters,
     * ordered by sort_order then name. Includes `hotelsCount`.
     */
    protected function catalogIndexQuery(Request $request, string $modelClass): Builder
    {
        $request->validate([
            'status' => ['sometimes', 'in:draft,published,archived'],
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        return $modelClass::query()
            ->withCount('hotels')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Accepts camelCase from the SPA (`sortOrder`, `imageUrl`) and maps
     * to snake_case columns; fills a unique slug from the name when the
     * caller omits it on create.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeCatalogPayload(array $data, string $modelClass, ?Model $existing = null): array
    {
        foreach (['sortOrder' => 'sort_order', 'imageUrl' => 'image_url'] as $camel => $snake) {
            if (array_key_exists($camel, $data)) {
                $data[$snake] = $data[$camel];
                unset($data[$camel]);
            }
        }

        if (array_key_exists('sort_order', $data) && $data['sort_order'] === null) {
            $data['sort_order'] = 0;
        }

        if (! $existing && empty($data['slug'])) {
            $base = Str::slug($data['name']) ?: 'item';
            $slug = $base;
            $i = 2;
            while ($modelClass::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }
            $data['slug'] = $slug;
        }

        return $data;
    }
}
