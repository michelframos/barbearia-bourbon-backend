<?php

namespace Database\Seeders;

use App\Modules\Core\Support\EmpresaContext;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Database\Seeder;

class ServicoSeeder extends Seeder
{
    public function run(): void
    {
        $idEmpresa = EmpresaContext::id();

        $servicos = [
            [
                'nome' => 'Corte',
                'duracao_minutos' => 30,
                'preco' => 45.00,
                'status' => 'a',
            ],
            [
                'nome' => 'Barba',
                'duracao_minutos' => 20,
                'preco' => 35.00,
                'status' => 'a',
            ],
            [
                'nome' => 'Corte + Barba',
                'duracao_minutos' => 45,
                'preco' => 70.00,
                'status' => 'a',
            ],
            [
                'nome' => 'Sobrancelha',
                'duracao_minutos' => 15,
                'preco' => 20.00,
                'status' => 'a',
            ],
        ];

        foreach ($servicos as $data) {
            $exists = ServicoModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('nome', $data['nome'])
                ->exists();

            if ($exists) {
                continue;
            }

            ServicoModel::create([
                'id_empresa' => $idEmpresa,
                ...$data,
            ]);
        }
    }
}
