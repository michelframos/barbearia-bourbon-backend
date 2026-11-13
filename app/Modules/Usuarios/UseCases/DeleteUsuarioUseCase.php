<?php

namespace App\Modules\Usuarios\UseCases;

use App\Modules\Usuarios\Contracts\UsuarioRepositoryInterface;
use Illuminate\Validation\ValidationException;

class DeleteUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id, ?int $authenticatedUserId = null): void
    {
        $model = $this->repository->findById($idEmpresa, $id);

        if ($this->repository->countByEmpresa($idEmpresa) <= 1) {
            throw ValidationException::withMessages([
                'id' => ['Não é possível excluir o último usuário da empresa.'],
            ]);
        }

        if ($authenticatedUserId !== null && (int) $model->id === $authenticatedUserId) {
            throw ValidationException::withMessages([
                'id' => ['Você não pode excluir o próprio usuário.'],
            ]);
        }

        $this->repository->delete($model);
    }
}
