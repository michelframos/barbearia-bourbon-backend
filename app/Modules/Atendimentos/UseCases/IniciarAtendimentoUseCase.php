<?php

namespace App\Modules\Atendimentos\UseCases;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Contracts\AtendimentoRepositoryInterface;
use App\Modules\Atendimentos\Contracts\StatusAtendimentoStrategyInterface;
use App\Modules\Atendimentos\DTOs\AtendimentoDTO;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Atendimentos\Services\AssistenteElegibilidadeService;
use App\Modules\Atendimentos\Services\AssistenteSelectionResolver;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Core\Services\AuditLogService;
use App\Modules\Core\Services\EmpresaConfigService;
use App\Modules\Core\Support\AuditAcao;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IniciarAtendimentoUseCase
{
    public function __construct(
        private readonly AtendimentoRepositoryInterface $repository,
        private readonly StatusAtendimentoStrategyInterface $statusStrategy,
        private readonly AssistenteSelectionResolver $assistenteSelectionResolver,
        private readonly AssistenteElegibilidadeService $elegibilidade,
        private readonly EmpresaConfigService $empresaConfig,
        private readonly AuditLogService $auditLog,
    ) {}

    public function execute(
        int $idEmpresa,
        string|int $id,
        ?int $idBarbeiro = null,
        ?int $idAssistente = null,
    ): AtendimentoModel {
        $model = $this->repository->findById($idEmpresa, $id);

        $this->statusStrategy->assertPodeTransicionar(
            $model->status_atendimento,
            AtendimentoModel::STATUS_EM_ATENDIMENTO
        );

        $barbeiroId = $idBarbeiro ?? $model->id_barbeiro;
        $dataDia = $model->data?->format('Y-m-d') ?? now()->toDateString();
        $maxCapacidade = $this->empresaConfig->maxAtendimentosSimultaneosAssistente();

        if (! $barbeiroId) {
            throw ValidationException::withMessages([
                'id_barbeiro' => ['Informe o barbeiro para iniciar o atendimento.'],
            ]);
        }

        $barbeiro = BarbeiroModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $barbeiroId)
            ->where('status', 'a')
            ->first();

        if (! $barbeiro) {
            throw ValidationException::withMessages([
                'id_barbeiro' => ['Barbeiro inválido ou inativo.'],
            ]);
        }

        if ($barbeiro->status_operacional === 'offline') {
            throw ValidationException::withMessages([
                'id_barbeiro' => ['Este barbeiro não está no expediente (fez check-out).'],
            ]);
        }

        $barbeiroOcupado = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_barbeiro', $barbeiroId)
            ->whereDate('data', $dataDia)
            ->where('status_atendimento', AtendimentoModel::STATUS_EM_ATENDIMENTO)
            ->where('id', '!=', $model->id)
            ->exists();

        if ($barbeiro->status_operacional === 'em_atendimento' || $barbeiroOcupado) {
            throw ValidationException::withMessages([
                'id_barbeiro' => ['Este barbeiro já está em atendimento.'],
            ]);
        }

        $assistenteId = $this->resolverAssistente(
            $idEmpresa,
            $dataDia,
            $model,
            (int) $barbeiroId,
            $idAssistente,
        );

        $assistenteOk = AssistenteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $assistenteId)
            ->where('status', 'a')
            ->exists();

        if (! $assistenteOk) {
            throw ValidationException::withMessages([
                'id_assistente' => ['Assistente inválida ou inativa.'],
            ]);
        }

        $posicaoFila = $model->posicao_fila ? (int) $model->posicao_fila : null;

        $assistenteMudou = (int) ($model->id_assistente ?? 0) !== (int) $assistenteId;
        if ($assistenteMudou || $posicaoFila === null) {
            $posicaoFila = $this->statusStrategy->proximaPosicaoFila(
                $idEmpresa,
                (int) $assistenteId,
                $dataDia,
                $maxCapacidade,
            );
        }

        $assistenteAnterior = $model->id_assistente ? (int) $model->id_assistente : null;
        $barbeiroAnterior = $model->id_barbeiro ? (int) $model->id_barbeiro : null;

        return DB::transaction(function () use (
            $model,
            $idEmpresa,
            $barbeiroId,
            $assistenteId,
            $posicaoFila,
            $assistenteAnterior,
            $barbeiroAnterior,
        ) {
            $updated = $this->repository->update($model, AtendimentoDTO::fromRequest([
                'id_assistente' => $assistenteId,
                'id_barbeiro' => $barbeiroId,
                'posicao_fila' => $posicaoFila,
                'hora_inicio' => now()->format('H:i'),
                'status_atendimento' => AtendimentoModel::STATUS_EM_ATENDIMENTO,
            ]));

            if ($updated->id_agendamento) {
                AgendamentoModel::query()
                    ->where('id_empresa', $idEmpresa)
                    ->where('id', $updated->id_agendamento)
                    ->update(['status_agenda' => AgendamentoModel::STATUS_EM_ATENDIMENTO]);
            }

            $this->statusStrategy->sincronizarOperacional($updated);

            if ($assistenteAnterior !== null && $assistenteAnterior !== (int) $assistenteId) {
                $anterior = new AtendimentoModel([
                    'id_empresa' => $idEmpresa,
                    'id_assistente' => $assistenteAnterior,
                    'id_barbeiro' => null,
                    'data' => $updated->data,
                ]);
                $this->statusStrategy->sincronizarOperacional($anterior);
            }

            if ($barbeiroAnterior !== null && $barbeiroAnterior !== (int) $barbeiroId) {
                $anterior = new AtendimentoModel([
                    'id_empresa' => $idEmpresa,
                    'id_assistente' => null,
                    'id_barbeiro' => $barbeiroAnterior,
                    'data' => $updated->data,
                ]);
                $this->statusStrategy->sincronizarOperacional($anterior);
            }

            $atendimento = $this->repository->findById($idEmpresa, $updated->id);

            $this->auditLog->registrar(
                AuditAcao::INICIAR,
                'atendimento',
                (int) $atendimento->id,
                sprintf(
                    'Atendimento iniciado: %s com %s',
                    $atendimento->cliente?->nome ?? 'Cliente',
                    $atendimento->barbeiro?->nome ?? 'Barbeiro',
                ),
                [
                    'id_assistente' => $atendimento->id_assistente,
                    'id_barbeiro' => $atendimento->id_barbeiro,
                    'status_atendimento' => $atendimento->status_atendimento,
                ],
            );

            return $atendimento;
        });
    }

    private function resolverAssistente(
        int $idEmpresa,
        string $dataDia,
        AtendimentoModel $model,
        int $barbeiroId,
        ?int $idAssistente,
    ): int {
        $empresa = $this->empresaConfig->getEmpresa();
        $jaAtribuida = $model->id_assistente ? (int) $model->id_assistente : null;

        if ($this->empresaConfig->isSelecaoAutomatica($empresa)
            && $jaAtribuida
            && $idAssistente === null
        ) {
            $assistenteAtual = AssistenteModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('id', $jaAtribuida)
                ->first();

            if ($assistenteAtual && $this->elegibilidade->assistenteElegivelParaBarbeiro(
                $assistenteAtual,
                $idEmpresa,
                $dataDia,
                $barbeiroId,
                $empresa,
                (int) $model->id,
            )) {
                return $jaAtribuida;
            }
        }

        $selectionStrategy = $this->assistenteSelectionResolver->resolver($empresa);

        return $selectionStrategy->resolver(
            $idEmpresa,
            $dataDia,
            $idAssistente ?? ($this->empresaConfig->isSelecaoAutomatica($empresa) ? null : $jaAtribuida),
            $barbeiroId,
            (int) $model->id,
        );
    }
}
