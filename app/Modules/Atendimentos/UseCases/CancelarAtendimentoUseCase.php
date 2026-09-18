<?php

namespace App\Modules\Atendimentos\UseCases;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Atendimentos\Contracts\AtendimentoRepositoryInterface;
use App\Modules\Atendimentos\Contracts\StatusAtendimentoStrategyInterface;
use App\Modules\Atendimentos\DTOs\AtendimentoDTO;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Core\Services\AuditLogService;
use App\Modules\Core\Support\AuditAcao;
use Illuminate\Support\Facades\DB;

class CancelarAtendimentoUseCase
{
    public function __construct(
        private readonly AtendimentoRepositoryInterface $repository,
        private readonly StatusAtendimentoStrategyInterface $statusStrategy,
        private readonly AuditLogService $auditLog,
    ) {}

    public function execute(int $idEmpresa, string|int $id): AtendimentoModel
    {
        $model = $this->repository->findById($idEmpresa, $id);

        $this->statusStrategy->assertPodeTransicionar(
            $model->status_atendimento,
            AtendimentoModel::STATUS_CANCELADO
        );

        return DB::transaction(function () use ($model, $idEmpresa) {
            $updated = $this->repository->update($model, AtendimentoDTO::fromRequest([
                'hora_fim' => $model->hora_fim ?? now()->format('H:i'),
                'status_atendimento' => AtendimentoModel::STATUS_CANCELADO,
            ]));

            if ($updated->id_agendamento) {
                AgendamentoModel::query()
                    ->where('id_empresa', $idEmpresa)
                    ->where('id', $updated->id_agendamento)
                    ->whereNotIn('status_agenda', [
                        AgendamentoModel::STATUS_CONCLUIDO,
                        AgendamentoModel::STATUS_CANCELADO,
                    ])
                    ->update(['status_agenda' => AgendamentoModel::STATUS_CANCELADO]);
            }

            $this->statusStrategy->sincronizarOperacional($updated);

            $atendimento = $this->repository->findById($idEmpresa, $updated->id);

            $this->auditLog->registrar(
                AuditAcao::CANCELAR,
                'atendimento',
                (int) $atendimento->id,
                sprintf(
                    'Atendimento cancelado: %s',
                    $atendimento->cliente?->nome ?? 'Cliente',
                ),
                ['status_atendimento' => $atendimento->status_atendimento],
            );

            return $atendimento;
        });
    }
}
