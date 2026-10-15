<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    private function rules(): array
    {
        return ['title' => ['required', 'max:150'], 'body' => ['nullable', 'max:5000']];
    }

    public function index(Request $request)
    {
        return view('notes.index', [
            'notes' => Note::where('user_id', $request->user()->id)->latest('updated_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Note::create($request->validate($this->rules()) + ['user_id' => $request->user()->id]);

        return back();
    }

    public function update(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 404);
        $note->update($request->validate($this->rules()));

        return back();
    }

    public function destroy(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 404);
        $note->delete();

        return back();
    }
}
