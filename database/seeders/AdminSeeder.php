<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Crea los usuarios administrativos para demo y desarrollo.
     *
     * Credenciales:
     *   Admin    → admin@tailoy.com  / password
     *   Soporte  → soporte@tailoy.com / password
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@tailoy.com'],
            [
                'full_name'     => 'Administrador Tai Loy',
                'password_hash' => Hash::make('password'),
                'role'          => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'soporte@tailoy.com'],
            [
                'full_name'     => 'Agente de Soporte',
                'password_hash' => Hash::make('password'),
                'role'          => 'support',
            ]
        );
    }
}
