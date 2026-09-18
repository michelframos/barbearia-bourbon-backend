<?php

namespace App\Modules\Assistentes\Contracts;

use App\Modules\Assistentes\DTOs\AssistenteDTO;
use App\Modules\Assistentes\Models\AssistenteModel;
use Illuminate\Pagination\LengthAwarePaginator;

interface AssistenteRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $idEmpresa, string|int $id): AssistenteModel;

    public function create(AssistenteDTO $dto): AssistenteModel;

    public function update(AssistenteModel $model, AssistenteDTO $dto): AssistenteModel;

    public function delete(AssistenteModel $model): void;

    public function restore(int $idEmpresa, string|int $id): AssistenteModel;

    /**
     * @param  list<array{id_barbeiro: int|string, dia_semana: int|string}>  $vinculos
     */
    public function syncVinculosBarbeiroDia(AssistenteModel $model, array $vinculos): void;
}
