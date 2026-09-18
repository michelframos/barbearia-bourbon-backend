<?php

namespace App\Modules\Servicos\UseCases;

use App\Modules\Servicos\Contracts\ServicoRepositoryInterface;
use App\Modules\Servicos\Models\ServicoModel;

class RestoreServicoUseCase
{
    public function __construct(
        private readonly ServicoRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): ServicoModel
    {
        return $this->repository->restore($idEmpresa, $id);
    }
}