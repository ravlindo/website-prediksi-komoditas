<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@mojokerto.go.id')],
            [
                'name' => env('ADMIN_NAME', 'Administrator Mojokerto'),
                'username' => env('ADMIN_USERNAME', 'admin_mojokerto'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'Admin12345!')),
                'is_active' => true,
            ]
        );
    }
}
