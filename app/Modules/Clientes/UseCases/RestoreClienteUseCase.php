<?php

namespace App\Modules\Clientes\UseCases;

use App\Modules\Clientes\Contracts\ClienteRepositoryInterface;
use App\Modules\Clientes\Models\ClienteModel;

class RestoreClienteUseCase
{
    public function __construct(
        private readonly ClienteRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): ClienteModel
    {
        return $this->repository->restore($idEmpresa, $id);
    }
}