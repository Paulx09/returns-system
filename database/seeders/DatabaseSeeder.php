<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'full_name' => 'Admin Sistema',
            'email' => 'admin@tailoy.com.pe',
            'password_hash' => Hash::make('password123'),
            'role' => 'admin',
        ]);
        
        // Support
        User::create([
            'full_name' => 'Soporte Cliente',
            'email' => 'soporte@tailoy.com.pe',
            'password_hash' => Hash::make('password123'),
            'role' => 'support',
        ]);

        $this->call([
            ReturnReasonSeeder::class,
            ExternalOrderCacheSeeder::class,
        ]);
    }
}
