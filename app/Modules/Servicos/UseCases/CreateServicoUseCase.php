<?php

namespace App\Modules\Servicos\UseCases;

use App\Modules\Servicos\Contracts\ServicoRepositoryInterface;
use App\Modules\Servicos\DTOs\ServicoDTO;
use App\Modules\Servicos\Models\ServicoModel;

class CreateServicoUseCase
{
    public function __construct(
        private readonly ServicoRepositoryInterface $repository
    ) {}

    public function execute(ServicoDTO $dto): ServicoModel
    {
        return $this->repository->create($dto);
    }
}