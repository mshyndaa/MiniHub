<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = ['user_id', 'title', 'body'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Announcements posted since the user last opened the page (older than their account never counts). */
    public static function unreadFor(User $user): int
    {
        $since = $user->notifications_read_at ?? $user->created_at;

        return static::where('created_at', '>', $since)->count();
    }
}
