<?php

namespace Database\Seeders;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $idEmpresa = EmpresaContext::id();

        $assistentesPorNome = AssistenteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->pluck('id', 'nome');

        $clientes = [
            [
                'nome' => 'Marcos Oliveira',
                'telefone' => '(11) 96666-3001',
                'assistente' => 'Ana Paula',
                'observacoes' => 'Cliente VIP — prefere corte clássico.',
                'status' => 'a',
            ],
            [
                'nome' => 'Pedro Santos',
                'telefone' => '(11) 96666-3002',
                'assistente' => 'Carla Souza',
                'observacoes' => null,
                'status' => 'a',
            ],
            [
                'nome' => 'Lucas Ferreira',
                'telefone' => '(11) 96666-3003',
                'assistente' => 'Ana Paula',
                'observacoes' => 'Alérgico a alguns produtos de barba.',
                'status' => 'a',
            ],
            [
                'nome' => 'Thiago Mendes',
                'telefone' => null,
                'assistente' => null,
                'observacoes' => null,
                'status' => 'a',
            ],
        ];

        foreach ($clientes as $data) {
            $assistenteNome = $data['assistente'];
            unset($data['assistente']);

            $exists = ClienteModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('nome', $data['nome'])
                ->exists();

            if ($exists) {
                continue;
            }

            ClienteModel::create([
                'id_empresa' => $idEmpresa,
                'id_assistente_preferida' => $assistenteNome
                    ? ($assistentesPorNome[$assistenteNome] ?? null)
                    : null,
                ...$data,
            ]);
        }
    }
}
