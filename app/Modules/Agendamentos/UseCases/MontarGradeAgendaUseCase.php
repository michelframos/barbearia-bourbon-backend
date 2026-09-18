<?php

namespace App\Modules\Agendamentos\UseCases;

use App\Modules\Agendamentos\Contracts\DisponibilidadeSlotStrategyInterface;

class MontarGradeAgendaUseCase
{
    public function __construct(
        private readonly DisponibilidadeSlotStrategyInterface $slotStrategy,
    ) {}

    /**
     * @return array{
     *     meta: array<string, mixed>,
     *     slots: list<array<string, mixed>>
     * }
     */
    public function execute(int $idEmpresa, string $data, ?int $idBarbeiro = null): array
    {
        return $this->slotStrategy->montarGrade($idEmpresa, $data, $idBarbeiro);
    }
}
