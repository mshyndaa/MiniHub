<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['note_id', 'title', 'body'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
