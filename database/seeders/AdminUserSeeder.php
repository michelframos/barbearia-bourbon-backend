<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()
            ->where('email', 'admin@barbearia.test')
            ->update(['email' => 'admin@email.com']);

        User::query()->updateOrCreate(
            ['email' => 'admin@email.com'],
            [
                'id_empresa' => EmpresaContext::id(),
                'name' => 'Administrador',
                'password' => 'Mudar@123',
                'perfil' => 'admin',
                'id_assistente' => null,
                'id_barbeiro' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}
