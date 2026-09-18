<?php

namespace App\Modules\Atendimentos\Observers;

use App\Modules\Atendimentos\Contracts\PainelTvSnapshotProxyInterface;
use App\Modules\Atendimentos\Models\AtendimentoModel;

class AtendimentoObserver
{
    public function __construct(
        private readonly PainelTvSnapshotProxyInterface $painelTvProxy,
    ) {}

    public function saved(AtendimentoModel $atendimento): void
    {
        $this->invalidarCache($atendimento);
    }

    public function deleted(AtendimentoModel $atendimento): void
    {
        $this->invalidarCache($atendimento);
    }

    private function invalidarCache(AtendimentoModel $atendimento): void
    {
        $data = $atendimento->data?->format('Y-m-d');

        if (! $data) {
            return;
        }

        $this->painelTvProxy->invalidar((int) $atendimento->id_empresa, $data);
    }
}
