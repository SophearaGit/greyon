<?php

namespace App\Models;

use Database\Factories\DeveloperFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A platform developer — the top of the access model, per the user's
 * confirmed-with-the-doc-author direction: "make a duplicate version
 * of breeze for developer (separate table for developer like admin)."
 *
 * Backed by its own `developers` table, its own `developer` guard
 * (config/auth.php), and its own Breeze-style auth controller tree
 * (app/Http/Controllers/Developer/Auth/*) — same pattern as `Admin`,
 * just one level up. Never created through a public route; seeded or
 * created via `php artisan tinker`.
 *
 * A developer isn't assigned packages/roles/features — they bypass
 * App\Services\AccessService::can() unconditionally. They're the ones
 * who manage packages/roles/features for everyone else.
 */
class Developer extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<DeveloperFactory> */
    use HasFactory, Notifiable, \Illuminate\Auth\MustVerifyEmail;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
