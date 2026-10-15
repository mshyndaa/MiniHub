<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        return view('todo.index', [
            'todos' => Todo::where('user_id', $request->user()->id)->orderBy('done')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        Todo::create($request->validate(['text' => ['required', 'max:200']]) + ['user_id' => $request->user()->id]);

        return back();
    }

    public function toggle(Request $request, Todo $todo)
    {
        abort_unless($todo->user_id === $request->user()->id, 404);
        $todo->update(['done' => ! $todo->done]);

        return back();
    }

    public function destroy(Request $request, Todo $todo)
    {
        abort_unless($todo->user_id === $request->user()->id, 404);
        $todo->delete();

        return back();
    }
}
