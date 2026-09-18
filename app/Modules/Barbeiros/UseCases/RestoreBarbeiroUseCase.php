<?php

namespace App\Modules\Barbeiros\UseCases;

use App\Modules\Barbeiros\Contracts\BarbeiroRepositoryInterface;
use App\Modules\Barbeiros\Models\BarbeiroModel;

class RestoreBarbeiroUseCase
{
    public function __construct(
        private readonly BarbeiroRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): BarbeiroModel
    {
        return $this->repository->restore($idEmpresa, $id);
    }
}