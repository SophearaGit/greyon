<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A flat, shared catalog entry (not scoped to one location/hotel) that
 * a hotel can opt into via the `hotel_product` pivot — see
 * App\Models\Hotel::products(). Client requirements doc (2026-10-04),
 * item 5. Managed via App\Http\Controllers\Admin\ProductController,
 * gated by the `products` feature (App\Services\AccessService) — same
 * feature-gated-create / global-seat-only-delete convention as every
 * other admin resource in this app.
 */
class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'status',
    ];

    /**
     * @return BelongsToMany<Hotel, $this>
     */
    public function hotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class);
    }
}
