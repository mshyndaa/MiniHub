<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Existing items go to the first admin (or the first user if there is no admin).
        $owner = DB::table('user_roles')->where('role', 'admin')->value('user_id') ?? DB::table('users')->orderBy('id')->value('id');
        if ($owner) {
            DB::table('stock_items')->whereNull('user_id')->update(['user_id' => $owner]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
