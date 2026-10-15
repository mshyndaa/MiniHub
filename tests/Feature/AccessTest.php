<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $roles = [], array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        foreach ($roles as $role) {
            $user->roles()->create(['role' => $role]);
        }

        return $user;
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/notes')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_without_role_is_blocked_from_every_mini_program(): void
    {
        $u = $this->user();
        $this->actingAs($u)->get('/')->assertOk()->assertSee('No access yet');
        $this->actingAs($u)->get('/notes')->assertForbidden();
        $this->actingAs($u)->get('/todo')->assertForbidden();
        $this->actingAs($u)->get('/admin/users')->assertForbidden();
        $this->actingAs($u)->post('/notes', ['title' => 'x'])->assertForbidden();
    }

    public function test_role_grants_only_its_own_program(): void
    {
        $u = $this->user(['notes']);
        $this->actingAs($u)->get('/notes')->assertOk();
        $this->actingAs($u)->get('/todo')->assertForbidden();
        $this->actingAs($u)->get('/admin/users')->assertForbidden();
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $target = $this->user();
        $u = $this->user(['notes']);
        $this->actingAs($u)->post("/admin/users/{$target->id}/reset-password")->assertForbidden();
        $this->actingAs($u)->put("/admin/users/{$target->id}/roles", ['roles' => ['admin']])->assertForbidden();
    }

    public function test_users_cannot_touch_each_others_notes(): void
    {
        $a = $this->user(['notes']);
        $b = $this->user(['notes']);
        $this->actingAs($a)->post('/notes', ['title' => 'mine']);
        $note = $a->hasMany(\App\Models\Note::class)->first();
        $this->actingAs($b)->delete("/notes/{$note->id}")->assertNotFound();
        $this->assertDatabaseHas('notes', ['id' => $note->id]);
    }

    public function test_admin_registers_user_and_forced_password_change(): void
    {
        $admin = $this->user(['admin']);
        $res = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Pip', 'email' => 'pip@example.com', 'roles' => ['todo'],
        ]);
        $res->assertSessionHas('temp');
        $temp = session('temp.password');

        $pip = User::where('email', 'pip@example.com')->first();
        $this->assertTrue($pip->must_change_password);
        $this->assertTrue($pip->hasRole('todo'));

        auth()->logout();
        $this->post('/login', ['email' => 'pip@example.com', 'password' => $temp])->assertRedirect('/');
        $this->get('/todo')->assertRedirect('/password');

        $this->put('/password', [
            'current_password' => $temp, 'password' => 'Brand-new-pass1', 'password_confirmation' => 'Brand-new-pass1',
        ])->assertRedirect('/');
        $this->get('/todo')->assertOk();
    }

    public function test_reset_password_forces_change_and_disabled_user_cannot_login(): void
    {
        $admin = $this->user(['admin']);
        $u = $this->user(['todo'], ['password' => 'old-password-1']);

        $this->actingAs($admin)->post("/admin/users/{$u->id}/reset-password")->assertSessionHas('temp');
        $this->assertTrue($u->fresh()->must_change_password);

        // Disable a second user whose password we still know: the correct password must be refused.
        $v = $this->user(['todo'], ['password' => 'known-password-1']);
        $this->actingAs($admin)->put("/admin/users/{$v->id}/active");
        $this->assertFalse($v->fresh()->is_active);
        auth()->logout();
        $this->post('/login', ['email' => $v->email, 'password' => 'known-password-1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = $this->user(['admin']);
        $this->actingAs($admin)->put("/admin/users/{$admin->id}/roles", ['roles' => ['notes']])
            ->assertSessionHasErrors('roles');
        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }
}
