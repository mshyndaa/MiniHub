<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the first admin. Change the password at first login (it is forced).
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@cjhub.test'],
            ['name' => 'Admin', 'password' => 'CJHub123', 'must_change_password' => false],
        );
        $admin->roles()->firstOrCreate(['role' => 'admin']);
    }
}
