<?php

namespace App\Modules\Clientes\UseCases;

use App\Modules\Clientes\Contracts\ClienteRepositoryInterface;
use App\Modules\Clientes\DTOs\ClienteDTO;
use App\Modules\Clientes\Models\ClienteModel;

class UpdateClienteUseCase
{
    public function __construct(
        private readonly ClienteRepositoryInterface $repository
    ) {}

    public function execute(ClienteModel $model, ClienteDTO $dto): ClienteModel
    {
        return $this->repository->update($model, $dto);
    }
}