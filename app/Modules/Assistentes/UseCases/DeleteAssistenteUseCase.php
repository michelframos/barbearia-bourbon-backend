<?php

namespace App\Modules\Assistentes\UseCases;

use App\Modules\Assistentes\Contracts\AssistenteRepositoryInterface;

class DeleteAssistenteUseCase
{
    public function __construct(
        private readonly AssistenteRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): void
    {
        $model = $this->repository->findById($idEmpresa, $id);
        $this->repository->delete($model);
    }
}