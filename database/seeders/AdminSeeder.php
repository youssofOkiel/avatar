<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->create([
            'name' => 'المشرف',
            'email' => 'admin@avatar.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        User::query()->create([
            'name' => 'المدير العام',
            'email' => 'superadmin@avatar.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Superadmin,
            'email_verified_at' => now(),
        ]);
    }
}
