<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            [
                'email' => 'admin@local.test',
            ],
            [
                'name' => 'Development Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['super-admin']);
    }
}