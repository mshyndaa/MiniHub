<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Todo;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return view('profile', [
            'user' => $user,
            'apps' => $user->apps(),
            'stats' => [
                'notes' => $user->hasRole('notes') ? Note::where('user_id', $user->id)->count() : null,
                'done' => $user->hasRole('todo') ? Todo::where('user_id', $user->id)->where('done', true)->count() : null,
                'open' => $user->hasRole('todo') ? Todo::where('user_id', $user->id)->where('done', false)->count() : null,
            ],
        ]);
    }
}
