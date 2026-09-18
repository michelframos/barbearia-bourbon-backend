<?php

namespace App\Modules\Atendimentos\Contracts;

use App\Modules\Atendimentos\DTOs\AtendimentoDTO;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use Illuminate\Support\Collection;

interface AtendimentoRepositoryInterface
{
    /**
     * @return Collection<int, AtendimentoModel>
     */
    public function listByDate(
        int $idEmpresa,
        string $data,
        ?string $statusAtendimento = null
    ): Collection;

    public function findById(int $idEmpresa, string|int $id): AtendimentoModel;

    public function create(AtendimentoDTO $dto): AtendimentoModel;

    public function update(AtendimentoModel $model, AtendimentoDTO $dto): AtendimentoModel;

    public function delete(AtendimentoModel $model): void;
}
