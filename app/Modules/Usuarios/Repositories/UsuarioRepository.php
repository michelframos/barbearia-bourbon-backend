<?php

namespace App\Modules\Usuarios\Repositories;

use App\Models\User;
use App\Modules\Usuarios\Contracts\UsuarioRepositoryInterface;
use App\Modules\Usuarios\DTOs\UsuarioDTO;
use Illuminate\Pagination\LengthAwarePaginator;

class UsuarioRepository implements UsuarioRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->where('id_empresa', $idEmpresa)
            ->when(
                filled($search),
                fn ($q) => $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
            )
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findById(int $idEmpresa, string|int $id): User
    {
        return User::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function findTrashedById(int $idEmpresa, string|int $id): User
    {
        return User::onlyTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function create(UsuarioDTO $dto): User
    {
        return User::query()->create($dto->data);
    }

    public function update(User $model, UsuarioDTO $dto): User
    {
        $model->update($dto->data);

        return $model->fresh();
    }

    public function delete(User $model): void
    {
        $model->tokens()->delete();
        $model->delete();
    }

    public function restore(int $idEmpresa, string|int $id): User
    {
        $model = $this->findTrashedById($idEmpresa, $id);
        $model->restore();

        return $model->fresh();
    }

    public function countByEmpresa(int $idEmpresa): int
    {
        return User::query()->where('id_empresa', $idEmpresa)->count();
    }

    public function emailTakenByActive(string $email, ?int $ignoreId = null): bool
    {
        return User::query()
            ->where('email', $email)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
