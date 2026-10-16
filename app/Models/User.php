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
        'avatar_preset',
        'banner_path',
        'banner_preset',
        'tv_bg_path',
        'tv_layout',
        'tv_style',
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
            'tv_layout' => 'array',
            'tv_style' => 'array',
            'must_change_password' => 'boolean',
        ];
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_preset && in_array($this->avatar_preset, self::avatarKeys(), true)) {
            return asset("avatars/{$this->avatar_preset}.svg");
        }

        // Older accounts may still have an uploaded photo.
        return $this->avatar_path ? route("media", [$this, "avatar", "v" => $this->updated_at?->timestamp]) : null;
    }

    /** Every selectable preset key, like "ghibli/soot". */
    public static function avatarKeys(): array
    {
        $keys = [];
        foreach (config('avatars') as $group => $set) {
            foreach (array_keys($set['items']) as $slug) {
                $keys[] = "{$group}/{$slug}";
            }
        }

        return $keys;
    }

    public function bannerUrl(): ?string
    {
        if ($this->banner_preset && array_key_exists($this->banner_preset, config('banners'))) {
            return asset("banners/{$this->banner_preset}.svg");
        }

        // Older accounts may still have an uploaded banner.
        return $this->banner_path ? route("media", [$this, "banner", "v" => $this->updated_at?->timestamp]) : null;
    }

    public function tvBackgroundUrl(): ?string
    {
        return $this->tv_bg_path ? route("media", [$this, "tvbg", "v" => $this->updated_at?->timestamp]) : null;
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
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
        return Menu::orderBy('sort_order')->orderBy('id')->get()
            ->filter(fn (Menu $menu) => $this->hasRole(...$menu->roles))
            ->map(fn (Menu $menu) => $menu->toArray())
            ->values()
            ->all();
    }
}
