<?php

namespace App\Modules\Atendimentos\Contracts;

interface AssistenteSelectionStrategyInterface
{
    /**
     * Resolve a assistente para o atendimento.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resolver(
        int $idEmpresa,
        string $dataDia,
        ?int $idAssistenteInformado,
        ?int $idBarbeiro = null,
        ?int $excetoAtendimentoId = null,
    ): int;
}
