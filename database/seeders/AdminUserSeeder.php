<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@barbearia.test'],
            [
                'id_empresa' => EmpresaContext::id(),
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'perfil' => 'admin',
                'id_assistente' => null,
                'id_barbeiro' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}
