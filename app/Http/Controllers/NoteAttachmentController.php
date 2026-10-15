<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Files attached to a note. They live on the private disk and are only served to the note's owner. */
class NoteAttachmentController extends Controller
{
    private function note(Request $request, $noteId): Note
    {
        return Note::where('user_id', $request->user()->id)->findOrFail($noteId);
    }

    public function store(Request $request, $noteId)
    {
        $note = $this->note($request, $noteId);

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip'],
        ], [
            'file.max' => 'Files must be 10 MB or smaller.',
            'file.uploaded' => 'The file could not be uploaded. Make sure it is 10 MB or smaller.',
            'file.mimes' => 'That file type is not supported.',
        ]);

        abort_if($note->attachments()->count() >= 20, 422, 'A note can hold up to 20 attachments.');

        $file = $request->file('file');
        $attachment = $note->attachments()->create([
            'path' => $file->store("notes/{$note->id}", 'local'),
            'name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        return response()->json($attachment->toArray(), 201);
    }

    public function show(Request $request, $noteId, $id)
    {
        $attachment = $this->note($request, $noteId)->attachments()->findOrFail($id);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        $inline = $attachment->isImage() || $attachment->mime === 'application/pdf';

        return Storage::disk('local')->response($attachment->path, $attachment->name, [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ], $inline ? 'inline' : 'attachment');
    }

    public function destroy(Request $request, $noteId, $id)
    {
        $attachment = $this->note($request, $noteId)->attachments()->findOrFail($id);
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['ok' => true]);
    }
}
