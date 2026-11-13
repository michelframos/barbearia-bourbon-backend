<?php

namespace App\Modules\Usuarios\Contracts;

use App\Models\User;
use App\Modules\Usuarios\DTOs\UsuarioDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface UsuarioRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $idEmpresa, string|int $id): User;

    public function findTrashedById(int $idEmpresa, string|int $id): User;

    public function create(UsuarioDTO $dto): User;

    public function update(User $model, UsuarioDTO $dto): User;

    public function delete(User $model): void;

    public function restore(int $idEmpresa, string|int $id): User;

    public function countByEmpresa(int $idEmpresa): int;

    public function emailTakenByActive(string $email, ?int $ignoreId = null): bool;
}
