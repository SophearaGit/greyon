<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Contact-form enquiry from the public site. Global until hotel-scoped.
 */
class Enquiry extends Model
{
    protected $fillable = [
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
            'consent' => 'boolean',
        ];
    }
}
