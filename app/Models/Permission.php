<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A CRUD-able fine-grained capability (developer-managed — see
 * App\Http\Controllers\Developer\PermissionController), one level
 * under a Feature. `key` is what App\Services\AccessService::
 * hasPermission() checks against — not the same set `can()`/
 * `permission:<key>` route middleware check (that's Feature keys,
 * module-level). Replaces Round 8's informal `features.parent_key`
 * sub-feature convention with a real table (2026-09-16).
 */
class Permission extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'feature_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'feature_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Feature, $this>
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * @return BelongsToMany<Package, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_permission');
    }
}
