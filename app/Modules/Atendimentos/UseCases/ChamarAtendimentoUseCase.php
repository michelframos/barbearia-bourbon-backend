<?php

namespace App\Modules\Atendimentos\UseCases;

use App\Modules\Atendimentos\Contracts\AtendimentoRepositoryInterface;
use App\Modules\Atendimentos\Contracts\StatusAtendimentoStrategyInterface;
use App\Modules\Atendimentos\DTOs\AtendimentoDTO;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Core\Contracts\NotificadorInterface;
use App\Modules\Core\Services\AuditLogService;
use App\Modules\Core\Services\EmpresaConfigService;
use App\Modules\Core\Support\AuditAcao;
use Illuminate\Support\Facades\DB;

class ChamarAtendimentoUseCase
{
    public function __construct(
        private readonly AtendimentoRepositoryInterface $repository,
        private readonly StatusAtendimentoStrategyInterface $statusStrategy,
        private readonly AuditLogService $auditLog,
        private readonly NotificadorInterface $notificador,
        private readonly EmpresaConfigService $empresaConfig,
    ) {}

    public function execute(int $idEmpresa, string|int $id): AtendimentoModel
    {
        $model = $this->repository->findById($idEmpresa, $id);

        $this->statusStrategy->assertPodeTransicionar(
            $model->status_atendimento,
            AtendimentoModel::STATUS_CHAMADO
        );

        return DB::transaction(function () use ($model, $idEmpresa) {
            $updated = $this->repository->update($model, AtendimentoDTO::fromRequest([
                'status_atendimento' => AtendimentoModel::STATUS_CHAMADO,
            ]));

            $this->statusStrategy->sincronizarOperacional($updated);

            $atendimento = $this->repository->findById($idEmpresa, $updated->id);

            $this->auditLog->registrar(
                AuditAcao::CHAMAR,
                'atendimento',
                (int) $atendimento->id,
                sprintf(
                    'Cliente chamado: %s',
                    $atendimento->cliente?->nome ?? 'Cliente',
                ),
                ['status_atendimento' => $atendimento->status_atendimento],
            );

            if ($this->empresaConfig->notificacoesAtivas() && $atendimento->cliente?->telefone) {
                $this->notificador->enviar(
                    $atendimento->cliente->telefone,
                    sprintf('%s, sua vez chegou na barbearia.', $atendimento->cliente->nome),
                    [
                        'tipo' => 'chamar_cliente',
                        'id_atendimento' => $atendimento->id,
                        'id_cliente' => $atendimento->id_cliente,
                    ],
                );
            }

            return $atendimento;
        });
    }
}
