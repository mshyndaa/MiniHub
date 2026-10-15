<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRolesMenusTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->create(['role' => 'admin']);

        return $user;
    }

    public function test_non_admin_cannot_open_roles_or_menus(): void
    {
        $user = User::factory()->create();
        $user->roles()->create(['role' => 'notes']);

        $this->actingAs($user)->get('/admin/roles')->assertForbidden();
        $this->actingAs($user)->get('/admin/menus')->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/users')->assertOk();
        $this->get('/admin/roles')->assertOk();
        $this->get('/admin/menus')->assertOk();
    }

    public function test_role_crud_and_rename_cascades(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/roles', ['name' => 'finance', 'description' => 'Money'])->assertSessionHasNoErrors();
        $role = Role::where('name', 'finance')->firstOrFail();

        $member = User::factory()->create();
        $member->roles()->create(['role' => 'finance']);
        $menu = Menu::create(['name' => 'Ledger', 'symbol' => 'puzzle', 'color' => 'blue', 'route' => 'todo.index', 'roles' => ['finance']]);

        $this->put("/admin/roles/{$role->id}", ['name' => 'accounting'])->assertSessionHasNoErrors();
        $this->assertTrue($member->fresh()->hasRole('accounting'));
        $this->assertSame(['accounting'], $menu->fresh()->roles);

        $this->delete("/admin/roles/{$role->id}");
        $this->assertDatabaseMissing('user_roles', ['role' => 'accounting']);
        $this->assertSame([], $menu->fresh()->roles);
    }

    public function test_built_in_roles_are_protected(): void
    {
        $admin = $this->admin();
        $role = Role::where('name', 'admin')->firstOrFail();

        $this->actingAs($admin)->delete("/admin/roles/{$role->id}")->assertSessionHasErrors('role');
        $this->put("/admin/roles/{$role->id}", ['name' => 'renamed', 'description' => 'x']);
        $this->assertSame('admin', $role->fresh()->name);
    }

    public function test_menu_crud_drives_the_sidebar(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/menus', ['name' => 'Ledger', 'symbol' => 'puzzle', 'color' => 'blue', 'route' => 'todo.index', 'roles' => ['todo']])
            ->assertSessionHasNoErrors();
        $menu = Menu::where('name', 'Ledger')->firstOrFail();

        $viewer = User::factory()->create();
        $viewer->roles()->create(['role' => 'todo']);
        $this->assertContains('Ledger', array_column($viewer->fresh()->apps(), 'name'));

        $this->put("/admin/menus/{$menu->id}", ['name' => 'Books', 'symbol' => 'puzzle', 'color' => 'pink', 'route' => 'todo.index', 'roles' => ['notes']]);
        $this->assertNotContains('Books', array_column($viewer->fresh()->apps(), 'name'));

        $this->delete("/admin/menus/{$menu->id}");
        $this->assertModelMissing($menu);
    }

    public function test_menu_rejects_unknown_route_and_protects_built_ins(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/menus', ['name' => 'Bad', 'symbol' => 'puzzle', 'color' => 'blue', 'route' => 'nope.nothing', 'roles' => ['todo']])
            ->assertSessionHasErrors('route');

        $builtIn = Menu::where('route', 'admin.users.index')->firstOrFail();
        $this->delete("/admin/menus/{$builtIn->id}")->assertSessionHasErrors('menu');
        $this->assertModelExists($builtIn);
    }
}
