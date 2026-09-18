<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Support\EmpresaContext;

class EmpresaConfigService
{
    public function getEmpresa(): Empresa
    {
        return Empresa::query()->findOrFail(EmpresaContext::id());
    }

    public function maxAtendimentosSimultaneosAssistente(?Empresa $empresa = null): int
    {
        $empresa ??= $this->getEmpresa();

        return max(1, (int) $empresa->max_atendimentos_simultaneos_assistente);
    }

    public function isSelecaoAutomatica(?Empresa $empresa = null): bool
    {
        $empresa ??= $this->getEmpresa();

        return $empresa->modo_selecao_assistente === Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL;
    }

    public function fonteOrdemAssistente(?Empresa $empresa = null): string
    {
        $empresa ??= $this->getEmpresa();

        return $empresa->fonte_ordem_assistente ?: Empresa::FONTE_ORDEM_MANUAL;
    }

    public function isFonteOrdemCheckin(?Empresa $empresa = null): bool
    {
        return $this->fonteOrdemAssistente($empresa) === Empresa::FONTE_ORDEM_CHECKIN;
    }

    public function resetCursorAssistente(?Empresa $empresa = null): string
    {
        $empresa ??= $this->getEmpresa();

        return $empresa->reset_cursor_assistente ?: Empresa::RESET_CURSOR_MEIA_NOITE;
    }

    public function momentoAtribuicaoAssistente(?Empresa $empresa = null): string
    {
        $empresa ??= $this->getEmpresa();

        return $empresa->momento_atribuicao_assistente ?: Empresa::MOMENTO_ATRIBUICAO_INICIAR;
    }

    public function isAtribuicaoNoCheckinCliente(?Empresa $empresa = null): bool
    {
        return $this->momentoAtribuicaoAssistente($empresa) === Empresa::MOMENTO_ATRIBUICAO_CHECKIN_CLIENTE;
    }

    public function notificacoesAtivas(?Empresa $empresa = null): bool
    {
        $empresa ??= $this->getEmpresa();

        return (bool) $empresa->notificacoes_ativas;
    }

    public function somAlertaAtivo(?Empresa $empresa = null): bool
    {
        $empresa ??= $this->getEmpresa();

        return (bool) $empresa->som_alerta_ativo;
    }

    public function atualizarCursorSequencial(int $idAssistente, ?Empresa $empresa = null): void
    {
        $empresa ??= $this->getEmpresa();

        $empresa->update([
            'ultima_assistente_sequencial_id' => $idAssistente,
            'cursor_sequencial_data' => now()->toDateString(),
        ]);
    }

    /**
     * Zera o cursor se a data do cursor não for hoje (reset lazy à meia-noite).
     */
    public function garantirCursorDoDia(?Empresa $empresa = null): Empresa
    {
        $empresa ??= $this->getEmpresa();

        if ($this->resetCursorAssistente($empresa) !== Empresa::RESET_CURSOR_MEIA_NOITE) {
            return $empresa;
        }

        $hoje = now()->toDateString();
        $dataCursor = $empresa->cursor_sequencial_data?->format('Y-m-d');

        if ($dataCursor === $hoje) {
            return $empresa;
        }

        $empresa->update([
            'ultima_assistente_sequencial_id' => null,
            'cursor_sequencial_data' => $hoje,
        ]);

        return $empresa->fresh() ?? $empresa;
    }

    /**
     * No primeiro check-in do dia, zera o cursor quando configurado.
     */
    public function resetCursorNoPrimeiroCheckin(?Empresa $empresa = null): Empresa
    {
        $empresa ??= $this->getEmpresa();

        if ($this->resetCursorAssistente($empresa) !== Empresa::RESET_CURSOR_PRIMEIRO_CHECKIN) {
            return $empresa;
        }

        $hoje = now()->toDateString();
        $dataCursor = $empresa->cursor_sequencial_data?->format('Y-m-d');

        if ($dataCursor === $hoje) {
            return $empresa;
        }

        $empresa->update([
            'ultima_assistente_sequencial_id' => null,
            'cursor_sequencial_data' => $hoje,
        ]);

        return $empresa->fresh() ?? $empresa;
    }

    /**
     * @return array{
     *     modo_selecao_assistente: string,
     *     max_atendimentos_simultaneos_assistente: int,
     *     fonte_ordem_assistente: string,
     *     reset_cursor_assistente: string,
     *     momento_atribuicao_assistente: string
     * }
     */
    public function configOperacional(?Empresa $empresa = null): array
    {
        $empresa ??= $this->getEmpresa();

        return [
            'modo_selecao_assistente' => $empresa->modo_selecao_assistente,
            'max_atendimentos_simultaneos_assistente' => $this->maxAtendimentosSimultaneosAssistente($empresa),
            'fonte_ordem_assistente' => $this->fonteOrdemAssistente($empresa),
            'reset_cursor_assistente' => $this->resetCursorAssistente($empresa),
            'momento_atribuicao_assistente' => $this->momentoAtribuicaoAssistente($empresa),
        ];
    }
}
