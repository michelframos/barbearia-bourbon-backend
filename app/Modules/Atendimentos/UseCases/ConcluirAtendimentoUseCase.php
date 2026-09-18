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

class ConcluirAtendimentoUseCase
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
            AtendimentoModel::STATUS_CONCLUIDO
        );

        return DB::transaction(function () use ($model, $idEmpresa) {
            $updated = $this->repository->update($model, AtendimentoDTO::fromRequest([
                'hora_fim' => now()->format('H:i'),
                'status_atendimento' => AtendimentoModel::STATUS_CONCLUIDO,
            ]));

            if ($updated->id_agendamento) {
                AgendamentoModel::query()
                    ->where('id_empresa', $idEmpresa)
                    ->where('id', $updated->id_agendamento)
                    ->update(['status_agenda' => AgendamentoModel::STATUS_CONCLUIDO]);
            }

            $this->statusStrategy->sincronizarOperacional($updated);

            $atendimento = $this->repository->findById($idEmpresa, $updated->id);

            $this->auditLog->registrar(
                AuditAcao::CONCLUIR,
                'atendimento',
                (int) $atendimento->id,
                sprintf(
                    'Atendimento concluído: %s',
                    $atendimento->cliente?->nome ?? 'Cliente',
                ),
                ['status_atendimento' => $atendimento->status_atendimento],
            );

            return $atendimento;
        });
    }
}
