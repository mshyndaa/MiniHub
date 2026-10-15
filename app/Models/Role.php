<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function userCount(): int
    {
        return UserRole::where('role', $this->name)->count();
    }
}
