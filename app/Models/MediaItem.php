<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Media library asset. Attribution via created_by_admin_id (nullOnDelete).
 */
class MediaItem extends Model
{
    protected $fillable = [
        'src',
        'alt',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'created_by_admin_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }
}
