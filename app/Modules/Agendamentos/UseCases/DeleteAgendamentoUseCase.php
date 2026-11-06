<?php

namespace App\Modules\Agendamentos\UseCases;

use App\Modules\Agendamentos\Contracts\AgendamentoRepositoryInterface;
use App\Modules\Agendamentos\DTOs\AgendamentoDTO;
use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Trinks\UseCases\EnviarAgendamentoParaTrinksUseCase;

class DeleteAgendamentoUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repository,
        private readonly EnviarAgendamentoParaTrinksUseCase $enviarTrinks,
    ) {}

    public function execute(int $idEmpresa, string|int $id): void
    {
        $model = $this->repository->findById($idEmpresa, $id);

        if ($model->status_agenda !== AgendamentoModel::STATUS_CANCELADO) {
            $this->repository->update($model, AgendamentoDTO::fromRequest([
                'status_agenda' => AgendamentoModel::STATUS_CANCELADO,
            ]));
            $model = $this->repository->findById($idEmpresa, $id);
        }

        $this->repository->delete($model);
        $this->enviarTrinks->execute($model, EnviarAgendamentoParaTrinksUseCase::ACAO_CANCELAR);
    }
}
