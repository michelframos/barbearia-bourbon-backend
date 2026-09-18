<?php

namespace App\Modules\Atendimentos\Services;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Services\EmpresaConfigService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssistenteElegibilidadeService
{
    public function __construct(
        private readonly EmpresaConfigService $empresaConfig,
    ) {}

    public function diaSemana(string $dataDia): int
    {
        return Carbon::parse($dataDia)->dayOfWeek;
    }

    public function temVinculo(
        int $idEmpresa,
        int $idAssistente,
        int $idBarbeiro,
        string $dataDia,
    ): bool {
        return DB::table('assistente_barbeiro_dia')
            ->where('id_empresa', $idEmpresa)
            ->where('id_assistente', $idAssistente)
            ->where('id_barbeiro', $idBarbeiro)
            ->where('dia_semana', $this->diaSemana($dataDia))
            ->exists();
    }

    public function temCheckinNoDia(AssistenteModel $assistente, string $dataDia): bool
    {
        if ($assistente->hora_checkin === null) {
            return false;
        }

        return $assistente->hora_checkin->toDateString() === $dataDia;
    }

    public function capacidadeDisponivel(
        AssistenteModel $assistente,
        int $idEmpresa,
        string $dataDia,
        ?int $max = null,
        ?int $excetoAtendimentoId = null,
    ): bool {
        $max ??= $this->empresaConfig->maxAtendimentosSimultaneosAssistente();

        $query = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_assistente', $assistente->id)
            ->whereDate('data', $dataDia)
            ->whereIn('status_atendimento', AtendimentoModel::STATUS_ABERTOS);

        if ($excetoAtendimentoId !== null) {
            $query->where('id', '!=', $excetoAtendimentoId);
        }

        return $query->count() < $max;
    }

    public function assistenteElegivelParaBarbeiro(
        AssistenteModel $assistente,
        int $idEmpresa,
        string $dataDia,
        int $idBarbeiro,
        ?Empresa $empresa = null,
        ?int $excetoAtendimentoId = null,
    ): bool {
        if ($assistente->status !== 'a') {
            return false;
        }

        if ($assistente->status_operacional === 'offline') {
            return false;
        }

        $empresa ??= $this->empresaConfig->getEmpresa();

        if ($this->empresaConfig->isFonteOrdemCheckin($empresa)
            && ! $this->temCheckinNoDia($assistente, $dataDia)) {
            return false;
        }

        if (! $this->temVinculo($idEmpresa, (int) $assistente->id, $idBarbeiro, $dataDia)) {
            return false;
        }

        return $this->capacidadeDisponivel(
            $assistente,
            $idEmpresa,
            $dataDia,
            $this->empresaConfig->maxAtendimentosSimultaneosAssistente($empresa),
            $excetoAtendimentoId,
        );
    }
}
