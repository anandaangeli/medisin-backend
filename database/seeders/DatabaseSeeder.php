<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'email' => 'admin@medisin.test', 'password' => 'password']
        );
    }
}
