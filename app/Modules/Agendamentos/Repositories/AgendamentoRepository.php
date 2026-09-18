<?php

namespace App\Modules\Agendamentos\Repositories;

use App\Modules\Agendamentos\Contracts\AgendamentoRepositoryInterface;
use App\Modules\Agendamentos\DTOs\AgendamentoDTO;
use App\Modules\Agendamentos\Models\AgendamentoModel;
use Illuminate\Support\Collection;

class AgendamentoRepository implements AgendamentoRepositoryInterface
{
    public function listByDate(int $idEmpresa, string $data, ?int $idBarbeiro = null): Collection
    {
        return AgendamentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->whereDate('data', $data)
            ->when($idBarbeiro !== null, fn ($q) => $q->where('id_barbeiro', $idBarbeiro))
            ->whereNotIn('status_agenda', [
                AgendamentoModel::STATUS_CANCELADO,
            ])
            ->with($this->defaultRelations($idEmpresa))
            ->orderBy('hora')
            ->get();
    }

    public function findById(int $idEmpresa, string|int $id): AgendamentoModel
    {
        return AgendamentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->with($this->defaultRelations($idEmpresa))
            ->firstOrFail();
    }

    public function create(AgendamentoDTO $dto): AgendamentoModel
    {
        $model = AgendamentoModel::create($dto->data);

        return $this->findById((int) $model->id_empresa, $model->id);
    }

    public function update(AgendamentoModel $model, AgendamentoDTO $dto): AgendamentoModel
    {
        $model->update($dto->data);

        return $this->findById((int) $model->id_empresa, $model->id);
    }

    public function delete(AgendamentoModel $model): void
    {
        $model->delete();
    }

    public function restore(int $idEmpresa, string|int $id): AgendamentoModel
    {
        $model = AgendamentoModel::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();

        $model->restore();

        return $this->findById($idEmpresa, $id);
    }

    /**
     * @return array<string, callable>
     */
    private function defaultRelations(int $idEmpresa): array
    {
        return [
            'cliente' => fn ($q) => $q->where('id_empresa', $idEmpresa),
            'assistente' => fn ($q) => $q->where('id_empresa', $idEmpresa),
            'barbeiro' => fn ($q) => $q->where('id_empresa', $idEmpresa),
            'servico' => fn ($q) => $q->where('id_empresa', $idEmpresa),
        ];
    }
}
