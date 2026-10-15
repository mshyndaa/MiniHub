<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    protected $fillable = ['user_id', 'name'];

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
