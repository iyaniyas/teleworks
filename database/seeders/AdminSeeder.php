<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'waduklalung@gmail.com'],
            [
                'name' => 'agus',
                'password' => Hash::make('DKtOSLgt'),
                'email_verified_at' => now(),
            ]
        );
    }
}

