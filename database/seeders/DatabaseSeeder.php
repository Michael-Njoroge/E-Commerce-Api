<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'firstname' => 'Michael',
            'lastname' => 'Njoroge',
            'email' => 'mikethecoder12@gmail.com',
            'mobile' => '0716002152',
            'role' => 'admin',
            'is_blocked' => false,
            'password' => Hash::make('password@123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
