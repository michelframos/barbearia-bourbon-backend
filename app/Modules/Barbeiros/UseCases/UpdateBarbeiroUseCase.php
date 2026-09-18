<?php

namespace App\Modules\Barbeiros\UseCases;

use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Atendimentos\UseCases\ConcluirAtendimentoUseCase;
use App\Modules\Barbeiros\Contracts\BarbeiroRepositoryInterface;
use App\Modules\Barbeiros\DTOs\BarbeiroDTO;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Core\Services\AuditLogService;
use App\Modules\Core\Support\AuditAcao;
use Illuminate\Support\Facades\DB;

class UpdateBarbeiroUseCase
{
    public function __construct(
        private readonly BarbeiroRepositoryInterface $repository,
        private readonly ConcluirAtendimentoUseCase $concluirAtendimentoUseCase,
        private readonly AuditLogService $auditLog,
    ) {}

    public function execute(BarbeiroModel $model, BarbeiroDTO $dto): BarbeiroModel
    {
        $statusAnterior = $model->status_operacional;
        $indoOffline = ($dto->data['status_operacional'] ?? null) === 'offline'
            && $model->status_operacional !== 'offline';

        $updated = ! $indoOffline
            ? $this->repository->update($model, $dto)
            : DB::transaction(function () use ($model, $dto) {
                $this->finalizarAtendimentosAtivos($model);

                return $this->repository->update($model, $dto);
            });

        $statusNovo = $dto->data['status_operacional'] ?? null;
        if ($statusNovo !== null && $statusNovo !== $statusAnterior) {
            $acao = $statusNovo === 'offline' ? AuditAcao::CHECK_OUT : AuditAcao::STATUS;

            $this->auditLog->registrar(
                $acao,
                'barbeiro',
                (int) $updated->id,
                sprintf(
                    'Status operacional de %s: %s → %s',
                    $updated->nome,
                    $statusAnterior,
                    $statusNovo,
                ),
                [
                    'status_operacional_anterior' => $statusAnterior,
                    'status_operacional_novo' => $statusNovo,
                ],
            );
        }

        return $updated;
    }

    private function finalizarAtendimentosAtivos(BarbeiroModel $model): void
    {
        $idEmpresa = (int) $model->id_empresa;

        $atendimentos = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_barbeiro', $model->id)
            ->whereDate('data', now()->toDateString())
            ->where('status_atendimento', AtendimentoModel::STATUS_EM_ATENDIMENTO)
            ->get();

        foreach ($atendimentos as $atendimento) {
            $this->concluirAtendimentoUseCase->execute($idEmpresa, $atendimento->id);
        }
    }
}
