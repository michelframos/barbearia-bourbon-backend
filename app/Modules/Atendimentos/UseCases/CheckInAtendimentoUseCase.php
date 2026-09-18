<?php

namespace App\Modules\Atendimentos\UseCases;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Contracts\AtendimentoRepositoryInterface;
use App\Modules\Atendimentos\Contracts\StatusAtendimentoStrategyInterface;
use App\Modules\Atendimentos\DTOs\AtendimentoDTO;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Atendimentos\Services\AssistenteSelectionResolver;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Core\Contracts\NotificadorInterface;
use App\Modules\Core\Services\AuditLogService;
use App\Modules\Core\Services\EmpresaConfigService;
use App\Modules\Core\Support\AuditAcao;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInAtendimentoUseCase
{
    public function __construct(
        private readonly AtendimentoRepositoryInterface $repository,
        private readonly StatusAtendimentoStrategyInterface $statusStrategy,
        private readonly AssistenteSelectionResolver $assistenteSelectionResolver,
        private readonly EmpresaConfigService $empresaConfig,
        private readonly AuditLogService $auditLog,
        private readonly NotificadorInterface $notificador,
    ) {}

    public function execute(AtendimentoDTO $dto): AtendimentoModel
    {
        $data = $dto->data;
        $idEmpresa = (int) $data['id_empresa'];
        $dataDia = (string) ($data['data'] ?? now()->toDateString());
        $data['data'] = $dataDia;
        $data['hora_chegada'] = $data['hora_chegada'] ?? now()->format('H:i');
        $data['status_atendimento'] = AtendimentoModel::STATUS_AGUARDANDO;
        $data['status'] = $data['status'] ?? 'a';
        $data['id_barbeiro'] = filled($data['id_barbeiro'] ?? null) ? (int) $data['id_barbeiro'] : null;
        $data['id_assistente'] = filled($data['id_assistente'] ?? null) ? (int) $data['id_assistente'] : null;
        $data['id_agendamento'] = filled($data['id_agendamento'] ?? null) ? (int) $data['id_agendamento'] : null;

        $empresa = $this->empresaConfig->getEmpresa();
        $atribuirAutomaticoNoCheckin = $this->empresaConfig->isSelecaoAutomatica($empresa)
            && $this->empresaConfig->isAtribuicaoNoCheckinCliente($empresa);

        if ($atribuirAutomaticoNoCheckin) {
            if ($data['id_barbeiro'] === null) {
                throw ValidationException::withMessages([
                    'id_barbeiro' => ['Informe o barbeiro para atribuir a assistente no check-in.'],
                ]);
            }

            $data['id_assistente'] = $this->assistenteSelectionResolver
                ->resolver($empresa)
                ->resolver(
                    $idEmpresa,
                    $dataDia,
                    null,
                    (int) $data['id_barbeiro'],
                );
        }

        $this->assertEntidades($idEmpresa, $data);

        if ($data['id_agendamento']) {
            $this->assertAgendamentoDisponivel($idEmpresa, (int) $data['id_agendamento']);
        }

        if ($data['id_assistente'] !== null) {
            $maxCapacidade = $this->empresaConfig->maxAtendimentosSimultaneosAssistente();

            $data['posicao_fila'] = isset($data['posicao_fila'])
                ? (int) $data['posicao_fila']
                : $this->statusStrategy->proximaPosicaoFila(
                    $idEmpresa,
                    (int) $data['id_assistente'],
                    $dataDia,
                    $maxCapacidade,
                );

            if ($data['posicao_fila'] < 1 || $data['posicao_fila'] > $maxCapacidade) {
                throw ValidationException::withMessages([
                    'posicao_fila' => ["Posição da fila deve ser entre 1 e {$maxCapacidade}."],
                ]);
            }

            $this->assertPosicaoLivre(
                $idEmpresa,
                (int) $data['id_assistente'],
                $dataDia,
                (int) $data['posicao_fila']
            );
        } else {
            $data['posicao_fila'] = null;
        }

        return DB::transaction(function () use ($data, $idEmpresa) {
            $atendimento = $this->repository->create(AtendimentoDTO::fromRequest($data));

            if ($atendimento->id_agendamento) {
                AgendamentoModel::query()
                    ->where('id_empresa', $idEmpresa)
                    ->where('id', $atendimento->id_agendamento)
                    ->update(['status_agenda' => AgendamentoModel::STATUS_CONFIRMADO]);
            }

            $this->statusStrategy->sincronizarOperacional($atendimento);

            $atendimento = $this->repository->findById($idEmpresa, $atendimento->id);

            $this->auditLog->registrar(
                AuditAcao::CHECK_IN,
                'atendimento',
                (int) $atendimento->id,
                sprintf(
                    'Check-in: %s — %s',
                    $atendimento->cliente?->nome ?? 'Cliente',
                    $atendimento->servico?->nome ?? 'Serviço',
                ),
                [
                    'id_cliente' => $atendimento->id_cliente,
                    'id_assistente' => $atendimento->id_assistente,
                    'id_barbeiro' => $atendimento->id_barbeiro,
                    'status_atendimento' => $atendimento->status_atendimento,
                ],
            );

            if ($this->empresaConfig->notificacoesAtivas()) {
                $destino = $atendimento->cliente?->telefone ?? 'equipe';
                $this->notificador->enviar(
                    $destino,
                    sprintf(
                        'Novo atendimento: %s — %s',
                        $atendimento->cliente?->nome ?? 'Cliente',
                        $atendimento->servico?->nome ?? 'Serviço',
                    ),
                    [
                        'tipo' => 'novo_atendimento',
                        'id_atendimento' => $atendimento->id,
                    ],
                );
            }

            return $atendimento;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertEntidades(int $idEmpresa, array $data): void
    {
        $clienteOk = ClienteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $data['id_cliente'])
            ->where('status', 'a')
            ->exists();

        if (! $clienteOk) {
            throw ValidationException::withMessages([
                'id_cliente' => ['Cliente inválido ou inativo.'],
            ]);
        }

        if ($data['id_assistente'] !== null) {
            $assistenteOk = AssistenteModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('id', $data['id_assistente'])
                ->where('status', 'a')
                ->exists();

            if (! $assistenteOk) {
                throw ValidationException::withMessages([
                    'id_assistente' => ['Assistente inválida ou inativa.'],
                ]);
            }
        }

        $servicoOk = ServicoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $data['id_servico'])
            ->where('status', 'a')
            ->exists();

        if (! $servicoOk) {
            throw ValidationException::withMessages([
                'id_servico' => ['Serviço inválido ou inativo.'],
            ]);
        }

        if ($data['id_barbeiro'] !== null) {
            $barbeiroOk = BarbeiroModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('id', $data['id_barbeiro'])
                ->where('status', 'a')
                ->exists();

            if (! $barbeiroOk) {
                throw ValidationException::withMessages([
                    'id_barbeiro' => ['Barbeiro inválido ou inativo.'],
                ]);
            }
        }
    }

    private function assertAgendamentoDisponivel(int $idEmpresa, int $idAgendamento): void
    {
        $agendamento = AgendamentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $idAgendamento)
            ->first();

        if (! $agendamento) {
            throw ValidationException::withMessages([
                'id_agendamento' => ['Agendamento não encontrado.'],
            ]);
        }

        if (in_array($agendamento->status_agenda, [
            AgendamentoModel::STATUS_CANCELADO,
            AgendamentoModel::STATUS_CONCLUIDO,
            AgendamentoModel::STATUS_NAO_COMPARECEU,
        ], true)) {
            throw ValidationException::withMessages([
                'id_agendamento' => ['Este agendamento não pode gerar check-in.'],
            ]);
        }

        $jaExiste = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_agendamento', $idAgendamento)
            ->whereIn('status_atendimento', AtendimentoModel::STATUS_ABERTOS)
            ->exists();

        if ($jaExiste) {
            throw ValidationException::withMessages([
                'id_agendamento' => ['Já existe atendimento aberto para este agendamento.'],
            ]);
        }
    }

    private function assertPosicaoLivre(
        int $idEmpresa,
        int $idAssistente,
        string $data,
        int $posicao
    ): void {
        $ocupada = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_assistente', $idAssistente)
            ->whereDate('data', $data)
            ->where('posicao_fila', $posicao)
            ->whereIn('status_atendimento', AtendimentoModel::STATUS_ABERTOS)
            ->exists();

        if ($ocupada) {
            throw ValidationException::withMessages([
                'posicao_fila' => ["A posição {$posicao} já está ocupada nesta assistente."],
            ]);
        }
    }
}
