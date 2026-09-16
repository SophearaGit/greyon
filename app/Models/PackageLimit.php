<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quantity cap on one package ("this package allows creating at
 * most N of `resource_key`") — developer-CRUD-able via
 * App\Http\Controllers\Developer\PackageController, read by
 * App\Services\AccessService::effectiveLimit(). See the
 * create_package_limits_table migration for why this is a separate
 * table/key-value shape rather than fixed columns on `packages`.
 */
class PackageLimit extends Model
{
    protected $fillable = [
        'package_id',
        'resource_key',
        'max_count',
    ];

    protected function casts(): array
    {
        return [
            'package_id' => 'integer',
            'max_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
