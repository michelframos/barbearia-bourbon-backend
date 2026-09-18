<?php

namespace App\Modules\Assistentes\UseCases;

use App\Modules\Assistentes\Contracts\AssistenteRepositoryInterface;
use App\Modules\Assistentes\DTOs\AssistenteDTO;
use App\Modules\Assistentes\Models\AssistenteModel;

class CreateAssistenteUseCase
{
    public function __construct(
        private readonly AssistenteRepositoryInterface $repository
    ) {}

    public function execute(AssistenteDTO $dto): AssistenteModel
    {
        return $this->repository->create($dto);
    }
}