<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CMS news / blog article. Global content (no hotel scope).
 */
class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
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
            'published_at' => 'date:Y-m-d',
        ];
    }
}
