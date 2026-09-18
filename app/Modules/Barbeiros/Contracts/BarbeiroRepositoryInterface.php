<?php

namespace App\Modules\Barbeiros\Contracts;

use App\Modules\Barbeiros\DTOs\BarbeiroDTO;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use Illuminate\Pagination\LengthAwarePaginator;

interface BarbeiroRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $idEmpresa, string|int $id): BarbeiroModel;

    public function create(BarbeiroDTO $dto): BarbeiroModel;

    public function update(BarbeiroModel $model, BarbeiroDTO $dto): BarbeiroModel;

    public function syncServicos(BarbeiroModel $model, array $servicoIds): void;

    public function updateFoto(BarbeiroModel $model, string $path): BarbeiroModel;

    public function clearFoto(BarbeiroModel $model): BarbeiroModel;

    public function delete(BarbeiroModel $model): void;

    public function restore(int $idEmpresa, string|int $id): BarbeiroModel;
}
