<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    protected $fillable = ['user_id', 'text', 'done'];

    protected function casts(): array
    {
        return ['done' => 'boolean'];
    }
}
