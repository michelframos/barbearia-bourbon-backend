<?php

namespace App\Modules\Barbeiros\UseCases;

use App\Modules\Barbeiros\Contracts\BarbeiroRepositoryInterface;
use App\Modules\Barbeiros\DTOs\BarbeiroDTO;
use App\Modules\Barbeiros\Models\BarbeiroModel;

class CreateBarbeiroUseCase
{
    public function __construct(
        private readonly BarbeiroRepositoryInterface $repository
    ) {}

    public function execute(BarbeiroDTO $dto): BarbeiroModel
    {
        return $this->repository->create($dto);
    }
}