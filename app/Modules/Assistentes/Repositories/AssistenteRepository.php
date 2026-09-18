<?php

namespace App\Modules\Assistentes\Repositories;

use App\Modules\Assistentes\Contracts\AssistenteRepositoryInterface;
use App\Modules\Assistentes\DTOs\AssistenteDTO;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AssistenteRepository implements AssistenteRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator
    {
        return AssistenteModel::query()
            ->where('id_empresa', $idEmpresa)
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

    public function findById(int $idEmpresa, string|int $id): AssistenteModel
    {
        return AssistenteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function create(AssistenteDTO $dto): AssistenteModel
    {
        $data = $dto->data;
        $syncVinculos = array_key_exists('vinculos_barbeiro_dia', $data);
        $vinculos = $data['vinculos_barbeiro_dia'] ?? [];
        unset($data['vinculos_barbeiro_dia']);

        $model = AssistenteModel::create($data);

        if ($syncVinculos) {
            $this->syncVinculosBarbeiroDia($model, $vinculos);
        }

        return $model->fresh() ?? $model;
    }

    public function update(AssistenteModel $model, AssistenteDTO $dto): AssistenteModel
    {
        $data = $dto->data;
        $syncVinculos = array_key_exists('vinculos_barbeiro_dia', $data);
        $vinculos = $data['vinculos_barbeiro_dia'] ?? [];
        unset($data['vinculos_barbeiro_dia']);

        if ($data !== []) {
            $model->update($data);
        }

        if ($syncVinculos) {
            $this->syncVinculosBarbeiroDia($model, $vinculos);
        }

        return $model->fresh() ?? $model;
    }

    public function delete(AssistenteModel $model): void
    {
        $model->delete();
    }

    public function restore(int $idEmpresa, string|int $id): AssistenteModel
    {
        $model = AssistenteModel::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();

        $model->restore();

        return $model->fresh() ?? $model;
    }

    /**
     * @param  list<array{id_barbeiro: int|string, dia_semana: int|string}>  $vinculos
     */
    public function syncVinculosBarbeiroDia(AssistenteModel $model, array $vinculos): void
    {
        DB::table('assistente_barbeiro_dia')
            ->where('id_empresa', $model->id_empresa)
            ->where('id_assistente', $model->id)
            ->delete();

        $rows = [];
        $seen = [];

        foreach ($vinculos as $vinculo) {
            $idBarbeiro = (int) ($vinculo['id_barbeiro'] ?? 0);
            $diaSemana = (int) ($vinculo['dia_semana'] ?? -1);

            if ($idBarbeiro < 1 || $diaSemana < 0 || $diaSemana > 6) {
                continue;
            }

            $key = "{$idBarbeiro}:{$diaSemana}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'id_empresa' => $model->id_empresa,
                'id_assistente' => $model->id,
                'id_barbeiro' => $idBarbeiro,
                'dia_semana' => $diaSemana,
            ];
        }

        if ($rows === []) {
            return;
        }

        $barbeiroIds = array_values(array_unique(array_column($rows, 'id_barbeiro')));
        $validIds = BarbeiroModel::query()
            ->where('id_empresa', $model->id_empresa)
            ->whereIn('id', $barbeiroIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validSet = array_flip($validIds);
        $rows = array_values(array_filter(
            $rows,
            fn (array $row) => isset($validSet[$row['id_barbeiro']])
        ));

        if ($rows !== []) {
            DB::table('assistente_barbeiro_dia')->insert($rows);
        }
    }
}
