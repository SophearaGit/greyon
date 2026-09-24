<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contact-form enquiry from the public site.
 * Optionally scoped to a destination (`location_id`) so managers
 * of that destination (and global admins) are notified.
 */
class Enquiry extends Model
{
    protected $fillable = [
        'location_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'consent',
        'status',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'location_id' => 'integer',
            'consent' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
