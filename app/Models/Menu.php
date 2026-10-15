<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    public const COLORS = ['blue', 'pink', 'yellow'];

    protected $fillable = ['name', 'description', 'icon', 'symbol', 'color', 'route', 'roles', 'sort_order', 'is_system'];

    protected function casts(): array
    {
        return ['roles' => 'array', 'is_system' => 'boolean'];
    }
}
