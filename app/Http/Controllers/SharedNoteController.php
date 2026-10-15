<?php

namespace App\Http\Controllers;

use App\Models\Note;

/** Read-only public page for a note its owner chose to share by link. */
class SharedNoteController extends Controller
{
    public function __invoke(string $token)
    {
        $note = Note::with(['user:id,name', 'tags'])->where('share_token', $token)->firstOrFail();

        return response()->view('notes.shared', ['note' => $note])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
