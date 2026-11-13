<?php

namespace App\Modules\Usuarios\UseCases;

use App\Models\User;
use App\Modules\Usuarios\Contracts\UsuarioRepositoryInterface;
use App\Modules\Usuarios\DTOs\UsuarioDTO;

class UpdateUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function execute(User $model, UsuarioDTO $dto): User
    {
        $data = $dto->data;

        if (! array_key_exists('password', $data) || blank($data['password'])) {
            unset($data['password']);
        }

        unset($data['perfil'], $data['id_assistente'], $data['id_barbeiro'], $data['id_empresa']);

        return $this->repository->update($model, new UsuarioDTO($data));
    }
}
