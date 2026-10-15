<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'must_change_password',
        'avatar_path',
        'banner_path',
    ];

    protected $attributes = [
        'is_active' => true,
        'must_change_password' => false,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? route("media", [$this, "avatar", "v" => $this->updated_at?->timestamp]) : null;
    }

    public function bannerUrl(): ?string
    {
        return $this->banner_path ? route("media", [$this, "banner", "v" => $this->updated_at?->timestamp]) : null;
    }

    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /** @return list<string> */
    public function roleNames(): array
    {
        return $this->roles->pluck('role')->all();
    }

    public function hasRole(string ...$roles): bool
    {
        return count(array_intersect($roles, $this->roleNames())) > 0;
    }

    /** Mini programs this user is allowed to open. */
    public function apps(): array
    {
        return array_filter(
            config('miniapps'),
            fn ($app) => $this->hasRole(...$app['roles'])
        );
    }
}
