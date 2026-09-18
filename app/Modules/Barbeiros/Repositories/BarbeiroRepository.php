<?php

namespace App\Modules\Barbeiros\Repositories;

use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Barbeiros\Contracts\BarbeiroRepositoryInterface;
use App\Modules\Barbeiros\DTOs\BarbeiroDTO;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BarbeiroRepository implements BarbeiroRepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = BarbeiroModel::query()
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

        $collection = $paginator->getCollection();
        $this->loadAtendimentosAtuais($collection, $idEmpresa);
        $collection->transform(fn (BarbeiroModel $barbeiro) => $this->loadServicos($barbeiro));

        return $paginator;
    }

    public function findById(int $idEmpresa, string|int $id): BarbeiroModel
    {
        $model = BarbeiroModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();

        $this->loadAtendimentosAtuais(collect([$model]), $idEmpresa);

        return $this->loadServicos($model);
    }

    public function create(BarbeiroDTO $dto): BarbeiroModel
    {
        $data = $dto->data;
        $servicoIds = $data['servico_ids'] ?? [];
        unset($data['servico_ids']);

        $model = BarbeiroModel::create($data);
        $this->syncServicos($model, $servicoIds);

        return $this->loadServicos($model->fresh());
    }

    public function update(BarbeiroModel $model, BarbeiroDTO $dto): BarbeiroModel
    {
        $data = $dto->data;
        $syncServicos = array_key_exists('servico_ids', $data);
        $servicoIds = $data['servico_ids'] ?? [];
        unset($data['servico_ids']);

        if ($data !== []) {
            $model->update($data);
        }

        if ($syncServicos) {
            $this->syncServicos($model, $servicoIds);
        }

        return $this->loadServicos($model->fresh());
    }

    public function syncServicos(BarbeiroModel $model, array $servicoIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $servicoIds)));

        DB::table('barbeiro_servico')
            ->where('id_empresa', $model->id_empresa)
            ->where('id_barbeiro', $model->id)
            ->delete();

        if ($ids === []) {
            return;
        }

        $validIds = ServicoModel::query()
            ->where('id_empresa', $model->id_empresa)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        $rows = array_map(
            fn (int $idServico) => [
                'id_empresa' => $model->id_empresa,
                'id_barbeiro' => $model->id,
                'id_servico' => $idServico,
            ],
            $validIds
        );

        if ($rows !== []) {
            DB::table('barbeiro_servico')->insert($rows);
        }
    }

    public function updateFoto(BarbeiroModel $model, string $path): BarbeiroModel
    {
        $model->update(['foto' => $path]);

        return $this->loadServicos($model->fresh());
    }

    public function clearFoto(BarbeiroModel $model): BarbeiroModel
    {
        $model->update(['foto' => null]);

        return $this->loadServicos($model->fresh());
    }

    public function delete(BarbeiroModel $model): void
    {
        $model->delete();
    }

    public function restore(int $idEmpresa, string|int $id): BarbeiroModel
    {
        $model = BarbeiroModel::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();

        $model->restore();

        return $this->loadServicos($model->fresh());
    }

    private function loadServicos(BarbeiroModel $model): BarbeiroModel
    {
        $servicos = ServicoModel::query()
            ->where('servicos.id_empresa', $model->id_empresa)
            ->whereIn('servicos.id', function ($query) use ($model): void {
                $query
                    ->select('id_servico')
                    ->from('barbeiro_servico')
                    ->where('id_empresa', $model->id_empresa)
                    ->where('id_barbeiro', $model->id);
            })
            ->orderBy('nome')
            ->get();

        $model->setRelation('servicos', $servicos);

        return $model;
    }

    /**
     * @param  Collection<int, BarbeiroModel>  $barbeiros
     */
    private function loadAtendimentosAtuais(Collection $barbeiros, int $idEmpresa): void
    {
        if ($barbeiros->isEmpty()) {
            return;
        }

        $ids = $barbeiros->pluck('id');
        $data = now()->toDateString();

        $atendimentos = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->whereDate('data', $data)
            ->where('status_atendimento', AtendimentoModel::STATUS_EM_ATENDIMENTO)
            ->whereIn('id_barbeiro', $ids)
            ->with([
                'cliente' => fn ($q) => $q->where('id_empresa', $idEmpresa),
                'servico' => fn ($q) => $q->where('id_empresa', $idEmpresa),
            ])
            ->get()
            ->keyBy('id_barbeiro');

        foreach ($barbeiros as $barbeiro) {
            $barbeiro->setRelation('atendimentoAtual', $atendimentos->get($barbeiro->id));
        }
    }
}
