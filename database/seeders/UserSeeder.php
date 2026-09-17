<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Kelana',
            'email' => 'admin@kelanacoffee.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Kasir Kelana',
            'email' => 'kasir@kelanacoffee.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_KASIR,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Inventory Kelana',
            'email' => 'inventory@kelanacoffee.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_INVENTORY,
            'email_verified_at' => now(),
        ]);
    }
}
