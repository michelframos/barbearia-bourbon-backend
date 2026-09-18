<?php

namespace App\Modules\Clientes\Contracts;

use App\Modules\Clientes\DTOs\ClienteDTO;
use App\Modules\Clientes\Models\ClienteModel;
use Illuminate\Pagination\LengthAwarePaginator;

interface ClienteRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $idEmpresa, string|int $id): ClienteModel;

    public function create(ClienteDTO $dto): ClienteModel;

    public function update(ClienteModel $model, ClienteDTO $dto): ClienteModel;

    public function delete(ClienteModel $model): void;

    public function restore(int $idEmpresa, string|int $id): ClienteModel;
}
