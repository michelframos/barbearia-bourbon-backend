<?php

namespace App\Modules\Usuarios\UseCases;

use App\Models\User;
use App\Modules\Usuarios\Contracts\UsuarioRepositoryInterface;
use App\Modules\Usuarios\DTOs\UsuarioDTO;

class CreateUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function execute(UsuarioDTO $dto): User
    {
        $data = $dto->data;
        $data['perfil'] = 'admin';
        $data['id_assistente'] = null;
        $data['id_barbeiro'] = null;

        return $this->repository->create(new UsuarioDTO($data));
    }
}
