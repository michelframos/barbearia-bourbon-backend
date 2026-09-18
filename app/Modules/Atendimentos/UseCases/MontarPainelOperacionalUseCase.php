<?php

namespace App\Modules\Atendimentos\UseCases;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Contracts\AtendimentoRepositoryInterface;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Core\Services\EmpresaConfigService;
use Illuminate\Support\Collection;

class MontarPainelOperacionalUseCase
{
    public function __construct(
        private readonly AtendimentoRepositoryInterface $repository,
        private readonly EmpresaConfigService $empresaConfig,
    ) {}

    /**
     * @return array{
     *     data: string,
     *     resumo: array{total: int, aguardando: int, chamados: int, em_atendimento: int, concluidos: int, cancelados: int},
     *     assistentes: list<array<string, mixed>>,
     *     barbeiros: list<array<string, mixed>>
     * }
     */
    public function execute(int $idEmpresa, string $data): array
    {
        $atendimentos = $this->repository->listByDate($idEmpresa, $data);
        $abertos = $atendimentos->filter(
            fn (AtendimentoModel $a) => in_array($a->status_atendimento, AtendimentoModel::STATUS_ABERTOS, true)
        );

        $assistentes = AssistenteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('status', 'a')
            ->orderBy('ordem_sequencial')
            ->orderBy('nome')
            ->get();

        $barbeiros = BarbeiroModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('status', 'a')
            ->orderBy('nome')
            ->get();

        $capacidade = $this->empresaConfig->maxAtendimentosSimultaneosAssistente();
        $porAssistente = $abertos->groupBy('id_assistente');

        $colunas = $assistentes->map(function (AssistenteModel $assistente) use ($porAssistente, $capacidade) {
            /** @var Collection<int, AtendimentoModel> $fila */
            $fila = $porAssistente->get($assistente->id, collect());
            $slots = [];

            for ($pos = 1; $pos <= $capacidade; $pos++) {
                $slots[$pos] = null;
            }

            foreach ($fila as $item) {
                $pos = (int) $item->posicao_fila;
                if (array_key_exists($pos, $slots)) {
                    $slots[$pos] = $this->mapAtendimento($item);
                }
            }

            $slotsPayload = [];
            for ($pos = 1; $pos <= $capacidade; $pos++) {
                $slotsPayload[] = ['posicao' => $pos, 'atendimento' => $slots[$pos]];
            }

            return [
                'id' => $assistente->id,
                'nome' => $assistente->nome,
                'turno' => $assistente->turno,
                'status_operacional' => $assistente->status_operacional,
                'capacidade' => $capacidade,
                'ocupados' => $fila->count(),
                'slots' => $slotsPayload,
            ];
        })->values()->all();

        return [
            'data' => $data,
            'config_operacional' => $this->empresaConfig->configOperacional(),
            'resumo' => [
                'total' => $abertos->count(),
                'aguardando' => $atendimentos->where('status_atendimento', AtendimentoModel::STATUS_AGUARDANDO)->count(),
                'chamados' => $atendimentos->where('status_atendimento', AtendimentoModel::STATUS_CHAMADO)->count(),
                'em_atendimento' => $atendimentos->where('status_atendimento', AtendimentoModel::STATUS_EM_ATENDIMENTO)->count(),
                'concluidos' => $atendimentos->where('status_atendimento', AtendimentoModel::STATUS_CONCLUIDO)->count(),
                'cancelados' => $atendimentos->where('status_atendimento', AtendimentoModel::STATUS_CANCELADO)->count(),
            ],
            'assistentes' => $colunas,
            'barbeiros' => $barbeiros->map(fn (BarbeiroModel $b) => [
                'id' => $b->id,
                'nome' => $b->nome,
                'status_operacional' => $b->status_operacional,
                'foto_url' => $b->foto ? '/storage/'.$b->foto : null,
            ])->values()->all(),
            'atendimentos' => $abertos->map(fn (AtendimentoModel $a) => $this->mapAtendimento($a))->values()->all(),
            'finalizados' => $atendimentos
                ->where('status_atendimento', AtendimentoModel::STATUS_CONCLUIDO)
                ->sortByDesc(fn (AtendimentoModel $a) => (string) $a->hora_fim)
                ->map(fn (AtendimentoModel $a) => $this->mapAtendimento($a))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAtendimento(AtendimentoModel $a): array
    {
        return [
            'id' => $a->id,
            'id_empresa' => $a->id_empresa,
            'id_agendamento' => $a->id_agendamento,
            'id_cliente' => $a->id_cliente,
            'id_assistente' => $a->id_assistente,
            'id_barbeiro' => $a->id_barbeiro,
            'id_servico' => $a->id_servico,
            'posicao_fila' => $a->posicao_fila,
            'data' => $a->data?->format('Y-m-d'),
            'hora_chegada' => $a->hora_chegada ? substr((string) $a->hora_chegada, 0, 5) : null,
            'hora_inicio' => $a->hora_inicio ? substr((string) $a->hora_inicio, 0, 5) : null,
            'hora_fim' => $a->hora_fim ? substr((string) $a->hora_fim, 0, 5) : null,
            'status_atendimento' => $a->status_atendimento,
            'observacoes' => $a->observacoes,
            'cliente' => $a->cliente ? [
                'id' => $a->cliente->id,
                'nome' => $a->cliente->nome,
                'telefone' => $a->cliente->telefone,
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
}
