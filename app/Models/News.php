<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CMS news / blog article. Optionally scoped to a destination
 * (location); null location_id = site-wide brand story.
 */
class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'location_id',
        'title',
        'slug',
        'cover_image',
        'excerpt',
        'body',
        'published_at',
        'status',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'location_id' => 'integer',
            'published_at' => 'date:Y-m-d',
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
