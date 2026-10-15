<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteFolderController extends Controller
{
    private function rules(): array
    {
        return ['name' => ['required', 'string', 'max:60']];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $folder = Folder::create(['user_id' => $request->user()->id, 'name' => trim($data['name'])]);

        return redirect()->route('notes.index', ['folder' => $folder->id])->with('status', 'Folder created.');
    }

    public function update(Request $request, $id)
    {
        $folder = Folder::where('user_id', $request->user()->id)->findOrFail($id);
        $folder->update(['name' => trim($request->validate($this->rules())['name'])]);

        return back()->with('status', 'Folder renamed.');
    }

    public function destroy(Request $request, $id)
    {
        $folder = Folder::where('user_id', $request->user()->id)->findOrFail($id);
        // The notes stay; they just lose their folder.
        Note::withTrashed()->where('folder_id', $folder->id)->update(['folder_id' => null]);
        $folder->delete();

        return redirect()->route('notes.index')->with('status', 'Folder deleted. Its notes were kept.');
    }
}
