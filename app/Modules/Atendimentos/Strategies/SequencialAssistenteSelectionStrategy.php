<?php

namespace App\Modules\Atendimentos\Strategies;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Contracts\AssistenteSelectionStrategyInterface;
use App\Modules\Atendimentos\Services\AssistenteElegibilidadeService;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Services\EmpresaConfigService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SequencialAssistenteSelectionStrategy implements AssistenteSelectionStrategyInterface
{
    public function __construct(
        private readonly EmpresaConfigService $empresaConfig,
        private readonly AssistenteElegibilidadeService $elegibilidade,
        private readonly ManualAssistenteSelectionStrategy $manualStrategy,
    ) {}

    public function resolver(
        int $idEmpresa,
        string $dataDia,
        ?int $idAssistenteInformado,
        ?int $idBarbeiro = null,
        ?int $excetoAtendimentoId = null,
    ): int {
        if ($idAssistenteInformado) {
            return $this->manualStrategy->resolver(
                $idEmpresa,
                $dataDia,
                $idAssistenteInformado,
                $idBarbeiro,
                $excetoAtendimentoId,
            );
        }

        if (! $idBarbeiro) {
            throw ValidationException::withMessages([
                'id_barbeiro' => ['Informe o barbeiro para atribuir a assistente.'],
            ]);
        }

        $empresa = $this->empresaConfig->garantirCursorDoDia();

        $assistentes = $this->montarFila($idEmpresa, $dataDia, $empresa);

        if ($assistentes->isEmpty()) {
            throw ValidationException::withMessages([
                'id_assistente' => $this->empresaConfig->isFonteOrdemCheckin($empresa)
                    ? ['Nenhuma assistente com check-in hoje.']
                    : ['Nenhuma assistente ativa cadastrada.'],
            ]);
        }

        $startIndex = $this->indiceInicial($assistentes, $empresa);

        for ($offset = 0; $offset < $assistentes->count(); $offset++) {
            /** @var AssistenteModel $assistente */
            $assistente = $assistentes[($startIndex + $offset) % $assistentes->count()];

            if ($this->elegibilidade->assistenteElegivelParaBarbeiro(
                $assistente,
                $idEmpresa,
                $dataDia,
                $idBarbeiro,
                $empresa,
                $excetoAtendimentoId,
            )) {
                $this->empresaConfig->atualizarCursorSequencial((int) $assistente->id, $empresa);

                return (int) $assistente->id;
            }
        }

        throw ValidationException::withMessages([
            'id_assistente' => ['Nenhuma assistente disponível no momento (vínculo, checkout ou capacidade máxima).'],
        ]);
    }

    /**
     * @return Collection<int, AssistenteModel>
     */
    private function montarFila(int $idEmpresa, string $dataDia, Empresa $empresa): Collection
    {
        $query = AssistenteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('status', 'a')
            ->where('status_operacional', '!=', 'offline');

        if ($this->empresaConfig->isFonteOrdemCheckin($empresa)) {
            $query
                ->whereNotNull('hora_checkin')
                ->whereDate('hora_checkin', $dataDia)
                ->orderBy('hora_checkin')
                ->orderBy('id');
        } else {
            $query
                ->orderBy('ordem_sequencial')
                ->orderBy('id');
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, AssistenteModel>  $assistentes
     */
    private function indiceInicial(Collection $assistentes, Empresa $empresa): int
    {
        $ultimaId = $empresa->ultima_assistente_sequencial_id;

        if ($ultimaId === null) {
            return 0;
        }

        $index = $assistentes->search(fn (AssistenteModel $a) => (int) $a->id === (int) $ultimaId);

        if ($index === false) {
            return 0;
        }

        return ((int) $index + 1) % $assistentes->count();
    }
}
