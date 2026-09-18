<?php

namespace App\Modules\Barbeiros\UseCases;

use App\Modules\Barbeiros\Contracts\BarbeiroRepositoryInterface;

class DeleteBarbeiroUseCase
{
    public function __construct(
        private readonly BarbeiroRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): void
    {
        $model = $this->repository->findById($idEmpresa, $id);
        $this->repository->delete($model);
    }
}