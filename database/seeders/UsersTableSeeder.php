<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
             [
                'id' => Str::uuid(),
                'firstname' => 'Michael',
                'lastname' => 'Njoroge',
                'email' => 'mikethecoder12@gmail.com',
                'mobile' => '0714802152',
                'role' => 'admin',
                'is_blocked' => false,
                'password' => Hash::make('password@123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'firstname' => 'Lucy',
                'lastname' => 'Nyambura',
                'email' => 'lucy12@gmail.com',
                'mobile' => '0716002152',
                'role' => 'user',
                'is_blocked' => false,
                'password' => Hash::make('password@123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
