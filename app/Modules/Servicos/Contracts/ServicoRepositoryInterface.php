<?php

namespace App\Modules\Servicos\Contracts;

use App\Modules\Servicos\DTOs\ServicoDTO;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Pagination\LengthAwarePaginator;

interface ServicoRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $idEmpresa, string|int $id): ServicoModel;

    public function create(ServicoDTO $dto): ServicoModel;

    public function update(ServicoModel $model, ServicoDTO $dto): ServicoModel;

    public function delete(ServicoModel $model): void;

    public function restore(int $idEmpresa, string|int $id): ServicoModel;
}