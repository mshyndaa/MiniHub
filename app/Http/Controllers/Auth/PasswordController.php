<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ]);

        $wasForced = $request->user()->must_change_password;

        $request->user()->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        return redirect()->route($wasForced ? 'dashboard' : 'profile')->with('status', 'Password updated.');
    }
}
