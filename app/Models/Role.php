<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A CRUD-able role label (developer-managed — see
 * App\Http\Controllers\Developer\RoleController). `is_global` marks a
 * role (conventionally `admin`) whose holders see everything
 * regardless of any package's location/hotel scope. `scope` declares
 * what *kind* of scoping a package granting this role implies: `none`
 * (default), `location` (that package assignment's `locationIds` — e.g.
 * `manager`), or `hotel` (that assignment's `hotelIds` — e.g.
 * `hotel_admin`); the actual ids live per assignment on the
 * `admin_package` pivot (App\Models\AdminPackage), not here.
 * App\Services\AccessService reads `is_global`/`scope` directly rather
 * than matching on role name, so a developer can add a brand-new
 * location- or hotel-scoped role and it works immediately — nothing in
 * the scoping logic is hard-coded to these two names.
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_global',
        'scope',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Package, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_role');
    }
}
