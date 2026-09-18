<?php

namespace Database\Seeders;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Core\Support\EmpresaContext;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AtendimentoSeeder extends Seeder
{
    public function run(): void
    {
        $idEmpresa = EmpresaContext::id();
        $hoje = Carbon::today()->toDateString();

        $clientes = ClienteModel::query()->where('id_empresa', $idEmpresa)->orderBy('id')->get();
        $assistentes = AssistenteModel::query()->where('id_empresa', $idEmpresa)->where('status', 'a')->orderBy('id')->get();
        $barbeiros = BarbeiroModel::query()->where('id_empresa', $idEmpresa)->where('status', 'a')->orderBy('id')->get();
        $servicos = ServicoModel::query()->where('id_empresa', $idEmpresa)->where('status', 'a')->orderBy('id')->get();

        if ($clientes->isEmpty() || $assistentes->isEmpty() || $barbeiros->isEmpty() || $servicos->isEmpty()) {
            return;
        }

        $seeds = [
            [
                'cliente' => $clientes[0],
                'assistente' => $assistentes[0],
                'barbeiro' => $barbeiros[0],
                'servico' => $servicos->firstWhere('nome', 'Corte + Barba') ?? $servicos[0],
                'posicao_fila' => 1,
                'hora_chegada' => '09:10',
                'hora_inicio' => '09:20',
                'status_atendimento' => AtendimentoModel::STATUS_EM_ATENDIMENTO,
                'observacoes' => 'VIP',
            ],
            [
                'cliente' => $clientes[1] ?? $clientes[0],
                'assistente' => $assistentes[0],
                'barbeiro' => $barbeiros[1] ?? $barbeiros[0],
                'servico' => $servicos->firstWhere('nome', 'Corte') ?? $servicos[0],
                'posicao_fila' => 2,
                'hora_chegada' => '09:35',
                'hora_inicio' => null,
                'status_atendimento' => AtendimentoModel::STATUS_CHAMADO,
                'observacoes' => null,
            ],
            [
                'cliente' => $clientes[2] ?? $clientes[0],
                'assistente' => $assistentes[1] ?? $assistentes[0],
                'barbeiro' => null,
                'servico' => $servicos->firstWhere('nome', 'Barba') ?? $servicos[0],
                'posicao_fila' => 1,
                'hora_chegada' => '10:00',
                'hora_inicio' => null,
                'status_atendimento' => AtendimentoModel::STATUS_AGUARDANDO,
                'observacoes' => null,
            ],
        ];

        foreach ($seeds as $seed) {
            $exists = AtendimentoModel::query()
                ->where('id_empresa', $idEmpresa)
                ->whereDate('data', $hoje)
                ->where('id_assistente', $seed['assistente']->id)
                ->where('posicao_fila', $seed['posicao_fila'])
                ->whereIn('status_atendimento', AtendimentoModel::STATUS_ABERTOS)
                ->exists();

            if ($exists) {
                continue;
            }

            AtendimentoModel::create([
                'id_empresa' => $idEmpresa,
                'id_agendamento' => null,
                'id_cliente' => $seed['cliente']->id,
                'id_assistente' => $seed['assistente']->id,
                'id_barbeiro' => $seed['barbeiro']?->id,
                'id_servico' => $seed['servico']->id,
                'posicao_fila' => $seed['posicao_fila'],
                'data' => $hoje,
                'hora_chegada' => $seed['hora_chegada'],
                'hora_inicio' => $seed['hora_inicio'],
                'hora_fim' => null,
                'status_atendimento' => $seed['status_atendimento'],
                'observacoes' => $seed['observacoes'],
                'status' => 'a',
            ]);
        }

        // Sync operacional básico após seed
        foreach ($assistentes as $assistente) {
            $abertos = AtendimentoModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('id_assistente', $assistente->id)
                ->whereDate('data', $hoje)
                ->whereIn('status_atendimento', AtendimentoModel::STATUS_ABERTOS)
                ->count();

            if ($assistente->status_operacional !== 'offline') {
                $assistente->update([
                    'status_operacional' => $abertos > 0 ? 'exclusiva' : 'livre',
                ]);
            }
        }

        foreach ($barbeiros as $barbeiro) {
            if ($barbeiro->status_operacional === 'offline') {
                continue;
            }

            $emAtendimento = AtendimentoModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('id_barbeiro', $barbeiro->id)
                ->whereDate('data', $hoje)
                ->where('status_atendimento', AtendimentoModel::STATUS_EM_ATENDIMENTO)
                ->exists();

            $aguardando = AtendimentoModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('id_barbeiro', $barbeiro->id)
                ->whereDate('data', $hoje)
                ->where('status_atendimento', AtendimentoModel::STATUS_AGUARDANDO)
                ->exists();

            $barbeiro->update([
                'status_operacional' => $emAtendimento
                    ? 'em_atendimento'
                    : ($aguardando ? 'aguardando' : 'disponivel'),
            ]);
        }
    }
}
