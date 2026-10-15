<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteAttachment extends Model
{
    protected $fillable = ['note_id', 'path', 'name', 'mime', 'size'];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function isImage(): bool
    {
        return in_array($this->mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'size' => $this->size,
            'is_image' => $this->isImage(),
            'url' => route('notes.attachments.show', [$this->note_id, $this->id]),
            'delete_url' => route('notes.attachments.destroy', [$this->note_id, $this->id]),
        ];
    }
}
