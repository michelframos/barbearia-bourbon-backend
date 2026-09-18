<?php

namespace App\Modules\Atendimentos\Contracts;

use App\Modules\Atendimentos\Models\AtendimentoModel;

interface StatusAtendimentoStrategyInterface
{
    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function assertPodeTransicionar(string $de, string $para): void;

    /**
     * Próxima posição livre (1..max) na fila da assistente no dia.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function proximaPosicaoFila(int $idEmpresa, int $idAssistente, string $data, ?int $maxCapacidade = null): int;

    public function sincronizarOperacional(AtendimentoModel $atendimento): void;
}
