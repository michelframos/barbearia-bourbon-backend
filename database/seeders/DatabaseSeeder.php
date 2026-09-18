<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EmpresaSeeder::class,
            AdminUserSeeder::class,
            AssistenteSeeder::class,
            ServicoSeeder::class,
            BarbeiroSeeder::class,
            ClienteSeeder::class,
            AgendamentoSeeder::class,
            AtendimentoSeeder::class,
        ]);
    }
}
