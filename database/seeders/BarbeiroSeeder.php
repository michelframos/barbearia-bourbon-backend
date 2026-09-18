<?php

namespace Database\Seeders;

use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Core\Support\EmpresaContext;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarbeiroSeeder extends Seeder
{
    public function run(): void
    {
        $idEmpresa = EmpresaContext::id();

        $servicosPorNome = ServicoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->pluck('id', 'nome');

        $barbeiros = [
            [
                'nome' => 'Rafael Costa',
                'telefone' => '(11) 97777-2001',
                'status_operacional' => 'em_atendimento',
                'hora_entrada' => '07:55',
                'status' => 'a',
                'servicos' => ['Corte', 'Barba', 'Corte + Barba'],
            ],
            [
                'nome' => 'Bruno Almeida',
                'telefone' => '(11) 97777-2002',
                'status_operacional' => 'disponivel',
                'hora_entrada' => '08:05',
                'status' => 'a',
                'servicos' => ['Corte', 'Corte + Barba', 'Sobrancelha'],
            ],
            [
                'nome' => 'Diego Martins',
                'telefone' => '(11) 97777-2003',
                'status_operacional' => 'disponivel',
                'hora_entrada' => '08:20',
                'status' => 'a',
                'servicos' => ['Barba', 'Sobrancelha'],
            ],
            [
                'nome' => 'Marco Aurélio',
                'telefone' => '(11) 97777-2004',
                'status_operacional' => 'offline',
                'hora_entrada' => null,
                'status' => 'a',
                'servicos' => ['Corte', 'Barba'],
            ],
        ];

        foreach ($barbeiros as $data) {
            $servicoNomes = $data['servicos'];
            unset($data['servicos']);

            $barbeiro = BarbeiroModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('nome', $data['nome'])
                ->first();

            if (! $barbeiro) {
                $barbeiro = BarbeiroModel::create([
                    'id_empresa' => $idEmpresa,
                    ...$data,
                ]);
            } else {
                $barbeiro->update($data);
            }

            $servicoIds = [];
            foreach ($servicoNomes as $nome) {
                if (isset($servicosPorNome[$nome])) {
                    $servicoIds[] = (int) $servicosPorNome[$nome];
                }
            }

            DB::table('barbeiro_servico')
                ->where('id_empresa', $idEmpresa)
                ->where('id_barbeiro', $barbeiro->id)
                ->delete();

            $rows = array_map(
                fn (int $idServico) => [
                    'id_empresa' => $idEmpresa,
                    'id_barbeiro' => $barbeiro->id,
                    'id_servico' => $idServico,
                ],
                $servicoIds
            );

            if ($rows !== []) {
                DB::table('barbeiro_servico')->insert($rows);
            }
        }
    }
}
