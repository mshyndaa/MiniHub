<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** Every role any mini program asks for. */
    private function availableRoles(): array
    {
        return collect(config('miniapps'))->pluck('roles')->flatten()->unique()->values()->all();
    }

    private function tempPassword(): string
    {
        return Str::password(12, symbols: false);
    }

    private function syncRoles(User $user, array $roles): void
    {
        $user->roles()->whereNotIn('role', $roles)->delete();
        foreach ($roles as $role) {
            $user->roles()->firstOrCreate(['role' => $role]);
        }
    }

    private function killSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    public function index()
    {
        return view('admin.users', [
            'users' => User::with('roles')->orderBy('name')->get(),
            'roles' => $this->availableRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'roles' => ['array'],
            'roles.*' => [Rule::in($this->availableRoles())],
        ]);

        $password = $this->tempPassword();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password,
            'must_change_password' => true,
        ]);
        $this->syncRoles($user, $data['roles'] ?? []);

        return back()->with('temp', ['user' => $user->name, 'email' => $user->email, 'password' => $password]);
    }

    public function updateRoles(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => [Rule::in($this->availableRoles())],
        ]);
        $roles = $data['roles'] ?? [];

        if ($user->is($request->user()) && ! in_array('admin', $roles, true)) {
            return back()->withErrors(['roles' => 'You cannot remove your own admin role.']);
        }

        $this->syncRoles($user, $roles);

        return back()->with('status', "Roles updated for {$user->name}.");
    }

    public function resetPassword(User $user)
    {
        $password = $this->tempPassword();
        $user->update(['password' => $password, 'must_change_password' => true]);
        $this->killSessions($user);

        return back()->with('temp', ['user' => $user->name, 'email' => $user->email, 'password' => $password]);
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['roles' => 'You cannot disable your own account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) {
            $this->killSessions($user);
        }

        return back()->with('status', $user->name.($user->is_active ? ' enabled.' : ' disabled.'));
    }
}
