<?php

namespace App\Modules\Agendamentos\UseCases;

use App\Modules\Agendamentos\Contracts\AgendamentoRepositoryInterface;
use App\Modules\Agendamentos\Models\AgendamentoModel;
use Illuminate\Validation\ValidationException;

class CancelAgendamentoUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repository,
    ) {}

    public function execute(int $idEmpresa, string|int $id): AgendamentoModel
    {
        $model = $this->repository->findById($idEmpresa, $id);

        if ($model->status_agenda === AgendamentoModel::STATUS_CANCELADO) {
            throw ValidationException::withMessages([
                'status_agenda' => ['Agendamento já está cancelado.'],
            ]);
        }

        if (in_array($model->status_agenda, [
            AgendamentoModel::STATUS_CONCLUIDO,
            AgendamentoModel::STATUS_EM_ATENDIMENTO,
        ], true)) {
            throw ValidationException::withMessages([
                'status_agenda' => ['Não é possível cancelar um agendamento em atendimento ou concluído.'],
            ]);
        }

        $updated = $this->repository->update($model, \App\Modules\Agendamentos\DTOs\AgendamentoDTO::fromRequest([
            'status_agenda' => AgendamentoModel::STATUS_CANCELADO,
        ]));

        $this->repository->delete($updated);

        return $updated;
    }
}
