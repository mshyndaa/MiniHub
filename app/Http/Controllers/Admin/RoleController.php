<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $counts = UserRole::selectRaw('role, count(*) as n')->groupBy('role')->pluck('n', 'role');

        return view('admin.roles', [
            'roles' => Role::orderBy('name')->get(),
            'counts' => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9_-]+$/', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:150'],
        ], ['name.regex' => 'Use lowercase letters, numbers, dashes or underscores only.']);

        Role::create($data);

        return back()->with('status', "Role {$data['name']} created.");
    }

    public function update(Request $request, Role $role)
    {
        $rules = ['description' => ['nullable', 'string', 'max:150']];
        if (! $role->is_system) {
            $rules['name'] = ['required', 'string', 'max:30', 'regex:/^[a-z0-9_-]+$/', Rule::unique('roles', 'name')->ignore($role->id)];
        }
        $data = $request->validate($rules, ['name.regex' => 'Use lowercase letters, numbers, dashes or underscores only.']);

        DB::transaction(function () use ($role, $data) {
            $old = $role->name;
            $role->update($data);

            // Keep assignments and menu access pointing at the renamed role.
            if ($role->name !== $old) {
                UserRole::where('role', $old)->update(['role' => $role->name]);
                Menu::all()->each(function (Menu $menu) use ($old, $role) {
                    if (in_array($old, $menu->roles, true)) {
                        $menu->update(['roles' => array_values(array_map(fn ($r) => $r === $old ? $role->name : $r, $menu->roles))]);
                    }
                });
            }
        });

        return back()->with('status', "Role {$role->name} updated.");
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => "The {$role->name} role is built in and cannot be deleted."]);
        }

        DB::transaction(function () use ($role) {
            UserRole::where('role', $role->name)->delete();
            Menu::all()->each(function (Menu $menu) use ($role) {
                if (in_array($role->name, $menu->roles, true)) {
                    $menu->update(['roles' => array_values(array_diff($menu->roles, [$role->name]))]);
                }
            });
            $role->delete();
        });

        return back()->with('status', "Role {$role->name} deleted.");
    }
}
