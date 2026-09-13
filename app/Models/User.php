<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A hotel customer (guest) or hotel/property manager.
 *
 * This is the ONLY model backed by the `users` table. Admins are a
 * completely separate model/table (see App\Models\Admin) — there is
 * no "admin" value in the `role` column here on purpose.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \Illuminate\Auth\MustVerifyEmail;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'role',
        'phone',
        'status',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isGuest(): bool
    {
        return $this->role === 'guest';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * True for an account created (or linked) via "Sign in with Google"
     * that has never set its own password.
     */
    public function hasNoPassword(): bool
    {
        return is_null($this->password);
    }
}
