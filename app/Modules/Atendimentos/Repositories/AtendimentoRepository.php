<?php

namespace App\Modules\Atendimentos\Repositories;

use App\Modules\Atendimentos\Contracts\AtendimentoRepositoryInterface;
use App\Modules\Atendimentos\DTOs\AtendimentoDTO;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use Illuminate\Support\Collection;

class AtendimentoRepository implements AtendimentoRepositoryInterface
{
    public function listByDate(
        int $idEmpresa,
        string $data,
        ?string $statusAtendimento = null
    ): Collection {
        return AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->whereDate('data', $data)
            ->when(
                filled($statusAtendimento),
                fn ($q) => $q->where('status_atendimento', $statusAtendimento)
            )
            ->with($this->defaultRelations($idEmpresa))
            ->orderBy('hora_chegada')
            ->orderBy('posicao_fila')
            ->get();
    }

    public function findById(int $idEmpresa, string|int $id): AtendimentoModel
    {
        return AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->with($this->defaultRelations($idEmpresa))
            ->firstOrFail();
    }

    public function create(AtendimentoDTO $dto): AtendimentoModel
    {
        $model = AtendimentoModel::create($dto->data);

        return $this->findById((int) $model->id_empresa, $model->id);
    }

    public function update(AtendimentoModel $model, AtendimentoDTO $dto): AtendimentoModel
    {
        $model->update($dto->data);

        return $this->findById((int) $model->id_empresa, $model->id);
    }

    public function delete(AtendimentoModel $model): void
    {
        $model->delete();
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
            'agendamento' => fn ($q) => $q->where('id_empresa', $idEmpresa),
        ];
    }
}
