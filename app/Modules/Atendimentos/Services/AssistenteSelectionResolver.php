<?php

namespace App\Modules\Atendimentos\Services;

use App\Modules\Atendimentos\Contracts\AssistenteSelectionStrategyInterface;
use App\Modules\Atendimentos\Strategies\ManualAssistenteSelectionStrategy;
use App\Modules\Atendimentos\Strategies\SequencialAssistenteSelectionStrategy;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Services\EmpresaConfigService;

class AssistenteSelectionResolver
{
    public function __construct(
        private readonly EmpresaConfigService $empresaConfig,
        private readonly ManualAssistenteSelectionStrategy $manualStrategy,
        private readonly SequencialAssistenteSelectionStrategy $sequencialStrategy,
    ) {}

    public function resolver(?Empresa $empresa = null): AssistenteSelectionStrategyInterface
    {
        $empresa ??= $this->empresaConfig->getEmpresa();

        if ($empresa->modo_selecao_assistente === Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL) {
            return $this->sequencialStrategy;
        }

        return $this->manualStrategy;
    }
}
