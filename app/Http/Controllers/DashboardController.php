<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Quick numbers shown on the matching app cards.
        $stats = [];
        if ($user->hasRole('notes')) {
            $stats['notes'] = ['n' => Note::where('user_id', $user->id)->count(), 'label' => 'notes'];
        }
        if ($user->hasRole('admin')) {
            $stats['admin'] = ['n' => User::count(), 'label' => 'users'];
        }

        return view('dashboard', ['apps' => $user->apps(), 'stats' => $stats]);
    }
}
