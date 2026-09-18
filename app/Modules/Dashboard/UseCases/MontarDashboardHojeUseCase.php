<?php

namespace App\Modules\Dashboard\UseCases;

use App\Modules\Agendamentos\Contracts\AgendamentoRepositoryInterface;
use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Contracts\AtendimentoRepositoryInterface;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Core\Models\Empresa;
use Carbon\Carbon;

class MontarDashboardHojeUseCase
{
    public function __construct(
        private readonly AtendimentoRepositoryInterface $atendimentoRepository,
        private readonly AgendamentoRepositoryInterface $agendamentoRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(int $idEmpresa, string $data): array
    {
        $empresa = Empresa::query()->findOrFail($idEmpresa);
        $atendimentos = $this->atendimentoRepository->listByDate($idEmpresa, $data);
        $agendamentos = $this->agendamentoRepository->listByDate($idEmpresa, $data);

        $assistentes = AssistenteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('status', 'a')
            ->orderBy('nome')
            ->get();

        $barbeiros = BarbeiroModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('status', 'a')
            ->orderBy('nome')
            ->get();

        $abertosPorAssistente = $atendimentos
            ->filter(fn (AtendimentoModel $a) => in_array($a->status_atendimento, AtendimentoModel::STATUS_ABERTOS, true))
            ->groupBy('id_assistente');

        return [
            'data' => $data,
            'kpis' => $this->montarKpis($atendimentos, $agendamentos, $assistentes, $barbeiros),
            'agenda' => $agendamentos->map(fn (AgendamentoModel $a) => $this->mapAgendamento($a))->values()->all(),
            'assistentes' => $assistentes->map(function (AssistenteModel $assistente) use ($abertosPorAssistente) {
                $fila = $abertosPorAssistente->get($assistente->id, collect());

                return [
                    'id' => $assistente->id,
                    'nome' => $assistente->nome,
                    'turno' => $assistente->turno,
                    'status_operacional' => $assistente->status_operacional,
                    'ocupados' => $fila->count(),
                    'capacidade' => 2,
                ];
            })->values()->all(),
            'barbeiros' => $barbeiros->map(fn (BarbeiroModel $b) => [
                'id' => $b->id,
                'nome' => $b->nome,
                'status_operacional' => $b->status_operacional,
                'foto_url' => $b->foto ? '/storage/'.$b->foto : null,
            ])->values()->all(),
            'grafico_horas' => $this->montarGraficoHoras($empresa, $agendamentos, $atendimentos),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AtendimentoModel>  $atendimentos
     * @param  \Illuminate\Support\Collection<int, AgendamentoModel>  $agendamentos
     * @param  \Illuminate\Support\Collection<int, AssistenteModel>  $assistentes
     * @param  \Illuminate\Support\Collection<int, BarbeiroModel>  $barbeiros
     * @return array<string, int>
     */
    private function montarKpis($atendimentos, $agendamentos, $assistentes, $barbeiros): array
    {
        $aguardando = $atendimentos->whereIn('status_atendimento', [
            AtendimentoModel::STATUS_AGUARDANDO,
            AtendimentoModel::STATUS_CHAMADO,
        ])->count();

        $emAtendimento = $atendimentos
            ->where('status_atendimento', AtendimentoModel::STATUS_EM_ATENDIMENTO)
            ->count();

        $trabalhando = $barbeiros
            ->where('status_operacional', 'em_atendimento')
            ->count();

        $livresAssistentes = $assistentes
            ->where('status_operacional', 'livre')
            ->count();

        $livresBarbeiros = $barbeiros
            ->where('status_operacional', 'disponivel')
            ->count();

        return [
            'trabalhando' => $trabalhando,
            'aguardando' => $aguardando,
            'em_atendimento' => $emAtendimento,
            'livres' => $livresAssistentes + $livresBarbeiros,
            'concluidos' => $atendimentos
                ->where('status_atendimento', AtendimentoModel::STATUS_CONCLUIDO)
                ->count(),
            'agendamentos_hoje' => $agendamentos->count(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AgendamentoModel>  $agendamentos
     * @param  \Illuminate\Support\Collection<int, AtendimentoModel>  $atendimentos
     * @return list<array{hora: string, agendamentos: int, concluidos: int, em_andamento: int}>
     */
    private function montarGraficoHoras(Empresa $empresa, $agendamentos, $atendimentos): array
    {
        $abertura = $this->normalizeTime((string) ($empresa->horario_abertura ?? '08:00:00'));
        $fechamento = $this->normalizeTime((string) ($empresa->horario_fechamento ?? '19:00:00'));
        $intervalo = max(30, (int) ($empresa->intervalo_agenda_minutos ?? 30));

        $slotsExpediente = $this->gerarSlots($abertura, $fechamento, $intervalo);

        $agendamentosPorSlot = $agendamentos->groupBy(
            fn (AgendamentoModel $a) => $this->horaParaSlot((string) $a->hora, $intervalo)
        );

        $concluidosPorSlot = $atendimentos
            ->where('status_atendimento', AtendimentoModel::STATUS_CONCLUIDO)
            ->groupBy(function (AtendimentoModel $a) use ($intervalo) {
                $hora = $a->hora_fim ?? $a->hora_inicio ?? $a->hora_chegada;

                return $this->horaParaSlot((string) $hora, $intervalo);
            });

        $emAndamentoPorSlot = $atendimentos
            ->where('status_atendimento', AtendimentoModel::STATUS_EM_ATENDIMENTO)
            ->groupBy(fn (AtendimentoModel $a) => $this->horaParaSlot(
                (string) ($a->hora_inicio ?? $a->hora_chegada),
                $intervalo
            ));

        $slotsComDados = $agendamentosPorSlot->keys()
            ->merge($concluidosPorSlot->keys())
            ->merge($emAndamentoPorSlot->keys())
            ->unique()
            ->values();

        $todosSlots = collect($slotsExpediente)
            ->merge($slotsComDados)
            ->unique()
            ->sortBy(fn (string $hora) => $this->minutosDesdeMeiaNoite($hora))
            ->values();

        return $todosSlots->map(function (string $hora) use (
            $agendamentosPorSlot,
            $concluidosPorSlot,
            $emAndamentoPorSlot
        ) {
            return [
                'hora' => $hora,
                'agendamentos' => $agendamentosPorSlot->get($hora, collect())->count(),
                'concluidos' => $concluidosPorSlot->get($hora, collect())->count(),
                'em_andamento' => $emAndamentoPorSlot->get($hora, collect())->count(),
            ];
        })->all();
    }

    /**
     * @return list<string>
     */
    private function gerarSlots(string $abertura, string $fechamento, int $intervalo): array
    {
        $cursor = Carbon::createFromFormat('H:i', $abertura);
        $fim = Carbon::createFromFormat('H:i', $fechamento);

        if (! $cursor || ! $fim || $cursor->gte($fim)) {
            return [];
        }

        $slots = [];

        while ($cursor->lt($fim)) {
            $slots[] = $cursor->format('H:i');
            $cursor->addMinutes($intervalo);
        }

        return $slots;
    }

    private function horaParaSlot(string $hora, int $intervalo): string
    {
        $normalizada = $this->normalizeTime($hora);
        $minutos = $this->minutosDesdeMeiaNoite($normalizada);
        $slot = (int) floor($minutos / $intervalo) * $intervalo;

        return sprintf('%02d:%02d', intdiv($slot, 60), $slot % 60);
    }

    private function minutosDesdeMeiaNoite(string $hora): int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})/', $hora, $matches)) {
            return 0;
        }

        return ((int) $matches[1] * 60) + (int) $matches[2];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAgendamento(AgendamentoModel $a): array
    {
        return [
            'id' => $a->id,
            'hora' => $a->hora ? substr((string) $a->hora, 0, 5) : null,
            'status_agenda' => $a->status_agenda,
            'observacoes' => $a->observacoes,
            'cliente' => $a->cliente ? [
                'id' => $a->cliente->id,
                'nome' => $a->cliente->nome,
            ] : null,
            'assistente' => $a->assistente ? [
                'id' => $a->assistente->id,
                'nome' => $a->assistente->nome,
            ] : null,
            'barbeiro' => $a->barbeiro ? [
                'id' => $a->barbeiro->id,
                'nome' => $a->barbeiro->nome,
            ] : null,
            'servico' => $a->servico ? [
                'id' => $a->servico->id,
                'nome' => $a->servico->nome,
                'duracao_minutos' => $a->servico->duracao_minutos,
            ] : null,
        ];
    }

    private function normalizeTime(string $time): string
    {
        $time = trim($time);

        if ($time === '') {
            return '00:00';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return '00:00';
    }
}
