<?php

namespace Database\Seeders;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Database\Seeder;

class AssistenteSeeder extends Seeder
{
    public function run(): void
    {
        $idEmpresa = EmpresaContext::id();

        $assistentes = [
            [
                'nome' => 'Ana Paula',
                'telefone' => '(11) 98888-1001',
                'turno' => 'Manhã',
                'status_operacional' => 'livre',
                'status' => 'a',
            ],
            [
                'nome' => 'Carla Souza',
                'telefone' => '(11) 98888-1002',
                'turno' => 'Tarde',
                'status_operacional' => 'livre',
                'status' => 'a',
            ],
            [
                'nome' => 'Juliana Reis',
                'telefone' => '(11) 98888-1003',
                'turno' => 'Manhã',
                'status_operacional' => 'offline',
                'status' => 'a',
            ],
        ];

        foreach ($assistentes as $data) {
            $exists = AssistenteModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('nome', $data['nome'])
                ->exists();

            if ($exists) {
                continue;
            }

            AssistenteModel::create([
                'id_empresa' => $idEmpresa,
                ...$data,
            ]);
        }
    }
}
