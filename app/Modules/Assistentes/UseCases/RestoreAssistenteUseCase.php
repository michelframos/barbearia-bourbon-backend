<?php

namespace App\Modules\Assistentes\UseCases;

use App\Modules\Assistentes\Contracts\AssistenteRepositoryInterface;
use App\Modules\Assistentes\Models\AssistenteModel;

class RestoreAssistenteUseCase
{
    public function __construct(
        private readonly AssistenteRepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): AssistenteModel
    {
        return $this->repository->restore($idEmpresa, $id);
    }
}