<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A CRUD-able module-level key (developer-managed — see
 * App\Http\Controllers\Developer\FeatureController). `key` is what
 * App\Services\AccessService::can() and the `permission:<key>` route
 * middleware check against — this is module *visibility*. Finer-
 * grained capability within a module is `permissions()` below (see
 * App\Models\Permission, 2026-09-16) — not the same thing as this
 * model's own `key`, despite the similar name.
 */
class Feature extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'category',
        'sort_order',
    ];

    /**
     * @return BelongsToMany<Package, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_feature');
    }

    /**
     * The fine-grained permissions catalogued under this module —
     * organizational only (what the package-builder UI shows once a
     * developer expands this feature), **not** an automatic grant
     * path. A package holding this feature doesn't thereby hold any of
     * these; each has to be picked individually via
     * `Package::permissions()`. See App\Models\Permission's docblock
     * and App\Services\AccessService::effectivePermissionKeys().
     *
     * @return HasMany<Permission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
