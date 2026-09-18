<?php

namespace App\Modules\Clientes\Repositories;

use App\Modules\Clientes\Contracts\ClienteRepositoryInterface;
use App\Modules\Clientes\DTOs\ClienteDTO;
use App\Modules\Clientes\Models\ClienteModel;
use Illuminate\Pagination\LengthAwarePaginator;

class ClienteRepository implements ClienteRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator
    {
        return ClienteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->with(['assistentePreferida' => function ($query) use ($idEmpresa): void {
                $query->where('id_empresa', $idEmpresa);
            }])
            ->when(
                filled($search),
                fn ($q) => $q->where(function ($query) use ($search): void {
                    $query
                        ->where('nome', 'like', "%{$search}%")
                        ->orWhere('telefone', 'like', "%{$search}%");
                })
            )
            ->orderBy('nome')
            ->paginate($perPage);
    }

    public function findById(int $idEmpresa, string|int $id): ClienteModel
    {
        return ClienteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->with(['assistentePreferida' => function ($query) use ($idEmpresa): void {
                $query->where('id_empresa', $idEmpresa);
            }])
            ->firstOrFail();
    }

    public function create(ClienteDTO $dto): ClienteModel
    {
        $model = ClienteModel::create($dto->data);

        return $this->findById((int) $model->id_empresa, $model->id);
    }

    public function update(ClienteModel $model, ClienteDTO $dto): ClienteModel
    {
        $model->update($dto->data);

        return $this->findById((int) $model->id_empresa, $model->id);
    }

    public function delete(ClienteModel $model): void
    {
        $model->delete();
    }

    public function restore(int $idEmpresa, string|int $id): ClienteModel
    {
        $model = ClienteModel::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();

        $model->restore();

        return $this->findById($idEmpresa, $id);
    }
}
