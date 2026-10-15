<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('icon', 16)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('stock_items')->insert([
            ['name' => 'Coffee', 'icon' => '☕', 'stock' => 500, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ice cream', 'icon' => '🍦', 'stock' => 500, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('roles')->insert([
            'name' => 'stock', 'description' => 'Hand out stock and show the TV display.',
            'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('menus')->insert([
            'name' => 'Stock', 'description' => 'Count coffee and ice cream as they go out.',
            'icon' => '☕', 'symbol' => 'inbox', 'color' => 'pink', 'route' => 'stock.index',
            'roles' => json_encode(['stock']), 'sort_order' => (int) DB::table('menus')->max('sort_order') + 1,
            'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Admins can open it straight away.
        foreach (DB::table('user_roles')->where('role', 'admin')->pluck('user_id') as $userId) {
            DB::table('user_roles')->insert(['user_id' => $userId, 'role' => 'stock']);
        }
    }

    public function down(): void
    {
        DB::table('user_roles')->where('role', 'stock')->delete();
        DB::table('menus')->where('route', 'stock.index')->delete();
        DB::table('roles')->where('name', 'stock')->delete();
        Schema::dropIfExists('stock_items');
    }
};
