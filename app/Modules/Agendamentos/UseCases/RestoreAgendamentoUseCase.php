<?php

namespace App\Modules\Agendamentos\UseCases;

use App\Modules\Agendamentos\Contracts\AgendamentoRepositoryInterface;
use App\Modules\Agendamentos\Models\AgendamentoModel;

class RestoreAgendamentoUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): AgendamentoModel
    {
        return $this->repository->restore($idEmpresa, $id);
    }
}