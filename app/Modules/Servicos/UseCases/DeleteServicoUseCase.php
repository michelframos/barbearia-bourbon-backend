<?php

namespace App\Modules\Servicos\UseCases;

use App\Modules\Servicos\Contracts\ServicoRepositoryInterface;

class DeleteServicoUseCase
{
    public function __construct(
        private readonly ServicoRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): void
    {
        $model = $this->repository->findById($idEmpresa, $id);
        $this->repository->delete($model);
    }
}