<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
            $table->string('description', 150)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('description', 150)->nullable();
            $table->string('icon', 16)->nullable();
            $table->string('symbol', 40)->default('puzzle');
            $table->string('color', 10)->default('blue');
            $table->string('route', 100);
            $table->json('roles');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();

        // Seed from the old config so existing installs keep working.
        $roles = [];
        foreach (config('miniapps', []) as $app) {
            foreach ($app['roles'] as $role) {
                $roles[$role] = [
                    'name' => $role,
                    'description' => $app['description'] ?? null,
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        DB::table('roles')->insert(array_values($roles));

        $order = 0;
        foreach (config('miniapps', []) as $app) {
            DB::table('menus')->insert([
                'name' => $app['name'],
                'description' => $app['description'] ?? null,
                'icon' => $app['icon'] ?? null,
                'symbol' => $app['symbol'],
                'color' => $app['color'],
                'route' => $app['route'],
                'roles' => json_encode($app['roles']),
                'sort_order' => $order++,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
        Schema::dropIfExists('roles');
    }
};
