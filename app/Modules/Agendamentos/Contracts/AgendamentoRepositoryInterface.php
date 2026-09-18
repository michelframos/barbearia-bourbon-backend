<?php

namespace App\Modules\Agendamentos\Contracts;

use App\Modules\Agendamentos\DTOs\AgendamentoDTO;
use App\Modules\Agendamentos\Models\AgendamentoModel;
use Illuminate\Support\Collection;

interface AgendamentoRepositoryInterface
{
    /**
     * @return Collection<int, AgendamentoModel>
     */
    public function listByDate(int $idEmpresa, string $data, ?int $idBarbeiro = null): Collection;

    public function findById(int $idEmpresa, string|int $id): AgendamentoModel;

    public function create(AgendamentoDTO $dto): AgendamentoModel;

    public function update(AgendamentoModel $model, AgendamentoDTO $dto): AgendamentoModel;

    public function delete(AgendamentoModel $model): void;

    public function restore(int $idEmpresa, string|int $id): AgendamentoModel;
}
