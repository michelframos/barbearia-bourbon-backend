<?php

namespace App\Modules\Clientes\UseCases;

use App\Modules\Clientes\Contracts\ClienteRepositoryInterface;
use App\Modules\Clientes\DTOs\ClienteDTO;
use App\Modules\Clientes\Models\ClienteModel;

class CreateClienteUseCase
{
    public function __construct(
        private readonly ClienteRepositoryInterface $repository
    ) {}

    public function execute(ClienteDTO $dto): ClienteModel
    {
        return $this->repository->create($dto);
    }
}