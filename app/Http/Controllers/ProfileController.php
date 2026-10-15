<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return view('profile', [
            'user' => $user,
            'apps' => $user->apps(),
        ]);
    }
}
