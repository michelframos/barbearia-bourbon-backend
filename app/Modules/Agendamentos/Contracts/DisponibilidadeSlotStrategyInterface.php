<?php

namespace App\Modules\Agendamentos\Contracts;

use App\Modules\Core\Models\Empresa;

interface DisponibilidadeSlotStrategyInterface
{
    /**
     * Gera a grade de horários do dia a partir da config da empresa.
     *
     * @return list<string> Horários no formato H:i (ex.: 08:00)
     */
    public function gerarSlots(Empresa $empresa): array;

    /**
     * Verifica se o barbeiro está livre no slot (anti double-booking).
     */
    public function estaDisponivel(
        int $idEmpresa,
        string $data,
        string $hora,
        int $idBarbeiro,
        ?int $excetoAgendamentoId = null
    ): bool;

    /**
     * Monta a grade do dia com ocupação (opcionalmente filtrada por barbeiro).
     *
     * @return array{
     *     meta: array{
     *         data: string,
     *         horario_abertura: string,
     *         horario_fechamento: string,
     *         intervalo_agenda_minutos: int,
     *         id_barbeiro: int|null
     *     },
     *     slots: list<array{
     *         hora: string,
     *         disponivel: bool,
     *         agendamento: array<string, mixed>|null
     *     }>
     * }
     */
    public function montarGrade(int $idEmpresa, string $data, ?int $idBarbeiro = null): array;
}
