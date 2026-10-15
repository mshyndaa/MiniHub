<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('roles')->insert([
            'name' => 'spin', 'description' => 'Use the spin wheel.',
            'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('menus')->insert([
            'name' => 'Spin Wheel', 'description' => 'Upload names from Excel and spin to pick winners.',
            'icon' => '🎡', 'symbol' => 'sparkles', 'color' => 'yellow', 'route' => 'spin.index',
            'roles' => json_encode(['spin']), 'sort_order' => (int) DB::table('menus')->max('sort_order') + 1,
            'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Admins can open it straight away.
        foreach (DB::table('user_roles')->where('role', 'admin')->pluck('user_id') as $userId) {
            DB::table('user_roles')->insert(['user_id' => $userId, 'role' => 'spin']);
        }
    }

    public function down(): void
    {
        DB::table('user_roles')->where('role', 'spin')->delete();
        DB::table('menus')->where('route', 'spin.index')->delete();
        DB::table('roles')->where('name', 'spin')->delete();
    }
};
