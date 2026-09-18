<?php

namespace App\Modules\Atendimentos\Strategies;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Contracts\AssistenteSelectionStrategyInterface;
use App\Modules\Atendimentos\Services\AssistenteElegibilidadeService;
use App\Modules\Core\Services\EmpresaConfigService;
use Illuminate\Validation\ValidationException;

class ManualAssistenteSelectionStrategy implements AssistenteSelectionStrategyInterface
{
    public function __construct(
        private readonly AssistenteElegibilidadeService $elegibilidade,
        private readonly EmpresaConfigService $empresaConfig,
    ) {}

    public function resolver(
        int $idEmpresa,
        string $dataDia,
        ?int $idAssistenteInformado,
        ?int $idBarbeiro = null,
        ?int $excetoAtendimentoId = null,
    ): int {
        if (! $idAssistenteInformado) {
            throw ValidationException::withMessages([
                'id_assistente' => ['Informe a assistente para iniciar o atendimento.'],
            ]);
        }

        if (! $idBarbeiro) {
            throw ValidationException::withMessages([
                'id_barbeiro' => ['Informe o barbeiro para atribuir a assistente.'],
            ]);
        }

        $assistente = AssistenteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $idAssistenteInformado)
            ->where('status', 'a')
            ->first();

        if (! $assistente) {
            throw ValidationException::withMessages([
                'id_assistente' => ['Assistente inválida ou inativa.'],
            ]);
        }

        if (! $this->elegibilidade->assistenteElegivelParaBarbeiro(
            $assistente,
            $idEmpresa,
            $dataDia,
            $idBarbeiro,
            $this->empresaConfig->getEmpresa(),
            $excetoAtendimentoId,
        )) {
            $mensagens = ['Assistente não elegível para este barbeiro neste dia.'];

            if ($assistente->status_operacional === 'offline') {
                $mensagens = ['Assistente está em checkout (offline).'];
            } elseif ($this->empresaConfig->isFonteOrdemCheckin()
                && ! $this->elegibilidade->temCheckinNoDia($assistente, $dataDia)) {
                $mensagens = ['Assistente não fez check-in hoje.'];
            } elseif (! $this->elegibilidade->temVinculo($idEmpresa, (int) $assistente->id, $idBarbeiro, $dataDia)) {
                $mensagens = ['Assistente não está configurada para trabalhar com este barbeiro neste dia da semana.'];
            } elseif (! $this->elegibilidade->capacidadeDisponivel(
                $assistente,
                $idEmpresa,
                $dataDia,
                null,
                $excetoAtendimentoId,
            )) {
                $mensagens = ['Assistente atingiu a capacidade máxima de atendimentos.'];
            }

            throw ValidationException::withMessages([
                'id_assistente' => $mensagens,
            ]);
        }

        return (int) $assistente->id;
    }
}
