<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    private function rules(?Menu $menu = null): array
    {
        $locked = $menu?->is_system;

        return [
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:16'],
            'symbol' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/'],
            'color' => ['required', Rule::in(Menu::COLORS)],
            'route' => [$locked ? 'prohibited' : 'required', 'string', 'max:100', fn ($attr, $value, $fail) => Route::has($value) || $fail('That route name does not exist.')],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(Role::pluck('name')->all())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function index()
    {
        return view('admin.menus', [
            'menus' => Menu::orderBy('sort_order')->orderBy('id')->get(),
            'roles' => Role::orderBy('name')->pluck('name')->all(),
            'colors' => Menu::COLORS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['sort_order'] ??= ((int) Menu::max('sort_order')) + 1;

        Menu::create($data);

        return back()->with('status', "Menu {$data['name']} created.");
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate($this->rules($menu));

        // The admin menu must stay reachable by admins.
        if ($menu->is_system && $menu->route === 'admin.users.index' && ! in_array('admin', $data['roles'], true)) {
            return back()->withErrors(['menu' => 'The Admin menu must keep the admin role.']);
        }

        $data['sort_order'] ??= $menu->sort_order;
        $menu->update($data);

        return back()->with('status', "Menu {$menu->name} updated.");
    }

    public function destroy(Menu $menu)
    {
        if ($menu->is_system) {
            return back()->withErrors(['menu' => "{$menu->name} is a built-in menu and cannot be deleted."]);
        }

        $menu->delete();

        return back()->with('status', "Menu {$menu->name} deleted.");
    }
}
