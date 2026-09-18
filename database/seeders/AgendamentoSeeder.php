<?php

namespace Database\Seeders;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Core\Support\EmpresaContext;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AgendamentoSeeder extends Seeder
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
                'hora' => '09:00',
                'cliente' => $clientes[0],
                'assistente' => $assistentes[0],
                'barbeiro' => $barbeiros[0],
                'servico' => $servicos->firstWhere('nome', 'Corte + Barba') ?? $servicos[0],
                'observacoes' => 'Cliente VIP',
                'status_agenda' => AgendamentoModel::STATUS_CONFIRMADO,
            ],
            [
                'hora' => '10:00',
                'cliente' => $clientes[1] ?? $clientes[0],
                'assistente' => $assistentes[1] ?? $assistentes[0],
                'barbeiro' => $barbeiros[1] ?? $barbeiros[0],
                'servico' => $servicos->firstWhere('nome', 'Corte') ?? $servicos[0],
                'observacoes' => null,
                'status_agenda' => AgendamentoModel::STATUS_AGENDADO,
            ],
            [
                'hora' => '11:30',
                'cliente' => $clientes[2] ?? $clientes[0],
                'assistente' => $assistentes[0],
                'barbeiro' => $barbeiros[0],
                'servico' => $servicos->firstWhere('nome', 'Barba') ?? $servicos[0],
                'observacoes' => null,
                'status_agenda' => AgendamentoModel::STATUS_AGENDADO,
            ],
            [
                'hora' => '14:00',
                'cliente' => $clientes[3] ?? $clientes[0],
                'assistente' => $assistentes[1] ?? $assistentes[0],
                'barbeiro' => $barbeiros[2] ?? $barbeiros[0],
                'servico' => $servicos->firstWhere('nome', 'Sobrancelha') ?? $servicos[0],
                'observacoes' => 'Primeira visita',
                'status_agenda' => AgendamentoModel::STATUS_AGENDADO,
            ],
        ];

        foreach ($seeds as $seed) {
            $exists = AgendamentoModel::query()
                ->where('id_empresa', $idEmpresa)
                ->whereDate('data', $hoje)
                ->whereTime('hora', $seed['hora'])
                ->where('id_barbeiro', $seed['barbeiro']->id)
                ->whereIn('status_agenda', AgendamentoModel::STATUS_BLOQUEANTES)
                ->exists();

            if ($exists) {
                continue;
            }

            // Garante vínculo barbeiro↔serviço para o seed
            $oferece = \Illuminate\Support\Facades\DB::table('barbeiro_servico')
                ->where('id_empresa', $idEmpresa)
                ->where('id_barbeiro', $seed['barbeiro']->id)
                ->where('id_servico', $seed['servico']->id)
                ->exists();

            if (! $oferece) {
                $primeiroServico = \Illuminate\Support\Facades\DB::table('barbeiro_servico')
                    ->where('id_empresa', $idEmpresa)
                    ->where('id_barbeiro', $seed['barbeiro']->id)
                    ->value('id_servico');

                if (! $primeiroServico) {
                    continue;
                }

                $seed['servico'] = $servicos->firstWhere('id', $primeiroServico) ?? $servicos[0];
            }

            AgendamentoModel::create([
                'id_empresa' => $idEmpresa,
                'data' => $hoje,
                'hora' => $seed['hora'],
                'id_cliente' => $seed['cliente']->id,
                'id_assistente' => $seed['assistente']->id,
                'id_barbeiro' => $seed['barbeiro']->id,
                'id_servico' => $seed['servico']->id,
                'observacoes' => $seed['observacoes'],
                'status_agenda' => $seed['status_agenda'],
                'status' => 'a',
            ]);
        }
    }
}
