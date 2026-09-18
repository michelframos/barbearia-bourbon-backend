<?php

namespace App\Modules\Clientes\UseCases;

use App\Modules\Clientes\Contracts\ClienteRepositoryInterface;

class DeleteClienteUseCase
{
    public function __construct(
        private readonly ClienteRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): void
    {
        $model = $this->repository->findById($idEmpresa, $id);
        $this->repository->delete($model);
    }
}