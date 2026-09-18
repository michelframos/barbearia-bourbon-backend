<?php

namespace App\Modules\Atendimentos\Contracts;

interface PainelTvSnapshotProxyInterface
{
    /**
     * @return array<string, mixed>
     */
    public function obterSnapshot(int $idEmpresa, string $data): array;

    public function invalidar(int $idEmpresa, string $data): void;
}
