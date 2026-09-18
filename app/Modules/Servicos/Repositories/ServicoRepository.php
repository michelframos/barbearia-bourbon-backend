<?php

namespace App\Modules\Servicos\Repositories;

use App\Modules\Servicos\Contracts\ServicoRepositoryInterface;
use App\Modules\Servicos\DTOs\ServicoDTO;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Pagination\LengthAwarePaginator;

class ServicoRepository implements ServicoRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator
    {
        return ServicoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->when(
                filled($search),
                fn ($q) => $q->where('nome', 'like', "%{$search}%")
            )
            ->orderBy('nome')
            ->paginate($perPage);
    }

    public function findById(int $idEmpresa, string|int $id): ServicoModel
    {
        return ServicoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function create(ServicoDTO $dto): ServicoModel
    {
        return ServicoModel::create($dto->data);
    }

    public function update(ServicoModel $model, ServicoDTO $dto): ServicoModel
    {
        $model->update($dto->data);

        return $model->fresh();
    }

    public function delete(ServicoModel $model): void
    {
        $model->delete();
    }

    public function restore(int $idEmpresa, string|int $id): ServicoModel
    {
        $model = ServicoModel::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();

        $model->restore();

        return $model->fresh();
    }
}
