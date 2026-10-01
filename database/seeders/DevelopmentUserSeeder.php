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
                'email' => ENV('DEVELOPMENT_USER_EMAIL', 'admin@local.test'),
            ],
            [
                'name' => 'Development Admin',
                'password' => Hash::make(ENV('DEVELOPMENT_USER_PASSWORD', 'password')),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['super-admin']);
    }
}