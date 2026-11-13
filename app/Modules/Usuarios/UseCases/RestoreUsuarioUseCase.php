<?php

namespace App\Modules\Usuarios\UseCases;

use App\Models\User;
use App\Modules\Usuarios\Contracts\UsuarioRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RestoreUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): User
    {
        $model = $this->repository->findTrashedById($idEmpresa, $id);

        if ($this->repository->emailTakenByActive($model->email, (int) $model->id)) {
            throw ValidationException::withMessages([
                'email' => ['Já existe um usuário ativo com este e-mail. Não é possível restaurar.'],
            ]);
        }

        return $this->repository->restore($idEmpresa, $id);
    }
}
