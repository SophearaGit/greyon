<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * The `admin_package` pivot — "user [admin] can have many packages" —
 * as a real model rather than a bare table, so `location_ids`/
 * `hotel_ids` get array casts. Each row is one package *assignment*
 * and carries its own scope: the same package can be granted to two
 * different admins with two different location/hotel scopes (e.g.
 * "Manager · Content+" for Phnom Penh vs. for Siem Reap), and one
 * admin can hold several differently-scoped packages at once. See
 * App\Models\Admin::packages() (`->using(self::class)`) and
 * App\Services\AccessService, which reads these per assignment rather
 * than from a flat column on the admin.
 */
class AdminPackage extends Pivot
{
    protected $table = 'admin_package';

    protected $fillable = [
        'admin_id',
        'package_id',
        'location_ids',
        'hotel_ids',
    ];

    protected function casts(): array
    {
        return [
            'location_ids' => 'array',
            'hotel_ids' => 'array',
        ];
    }

    /**
     * @return list<int>
     */
    public function locationIds(): array
    {
        return $this->location_ids ?? [];
    }

    /**
     * @return list<int>
     */
    public function hotelIds(): array
    {
        return $this->hotel_ids ?? [];
    }
}
