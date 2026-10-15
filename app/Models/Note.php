<?php

namespace App\Models;

use App\Support\NoteHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Note extends Model
{
    use SoftDeletes;

    public const TYPES = ['general' => 'Blank', 'study' => 'Study', 'work' => 'Work', 'meeting' => 'Meeting'];

    public const COLORS = ['none', 'yellow', 'pink', 'blue', 'green', 'violet'];

    protected $fillable = [
        'user_id', 'title', 'body', 'plain', 'type', 'folder_id', 'color',
        'pinned', 'favorite', 'archived_at', 'reminder_at',
    ];

    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
            'favorite' => 'boolean',
            'archived_at' => 'datetime',
            'reminder_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::forceDeleting(fn (Note $note) => Storage::disk('local')->deleteDirectory("notes/{$note->id}"));
    }

    /** Starting point for a new note of the given type. */
    public static function template(string $type): array
    {
        $checklist = '<ul data-checklist><li data-checked="false"></li></ul>';

        return match ($type) {
            'study' => [
                'title' => 'Study notes',
                'body' => '<p><strong>Subject:</strong> </p><p><strong>Topic:</strong> </p><h2>Key points</h2><ul><li></li></ul><h2>Questions</h2><ul><li></li></ul><h2>Summary</h2><p></p>',
            ],
            'work' => [
                'title' => 'Work notes',
                'body' => '<p><strong>Project:</strong> </p><h2>Notes</h2><p></p><h2>Next steps</h2>'.$checklist,
            ],
            'meeting' => [
                'title' => 'Meeting notes - '.now()->format('j M Y'),
                'body' => '<p><strong>Date:</strong> '.now()->format('j F Y').'</p><p><strong>Attendees:</strong> </p><h2>Agenda</h2><ul><li></li></ul><h2>Discussion</h2><p></p><h2>Action items</h2>'.$checklist,
            ],
            default => ['title' => '', 'body' => ''],
        };
    }

    /** Sets title/body from editor input, keeping the searchable plain text in sync. */
    public function setContent(?string $title, ?string $html): void
    {
        if ($title !== null) {
            $this->title = trim($title) === '' ? 'Untitled note' : trim($title);
        }
        if ($html !== null) {
            $this->body = NoteHtml::clean($html);
            $this->plain = NoteHtml::toText($this->body);
        }
    }

    public function preview(int $limit = 180): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', (string) $this->plain)), $limit);
    }

    public function wordCount(): int
    {
        return str_word_count(preg_replace('/[^\p{L}\p{N}\s\'-]+/u', ' ', (string) $this->plain)) ?: 0;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(NoteVersion::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(NoteAttachment::class);
    }
}
