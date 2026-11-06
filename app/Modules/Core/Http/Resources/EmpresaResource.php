<?php

namespace App\Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Core\Models\Empresa */
class EmpresaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'endereco' => $this->endereco,
            'horario_abertura' => $this->horario_abertura ? substr((string) $this->horario_abertura, 0, 5) : null,
            'horario_fechamento' => $this->horario_fechamento ? substr((string) $this->horario_fechamento, 0, 5) : null,
            'intervalo_agenda_minutos' => $this->intervalo_agenda_minutos,
            'painel_tv_auto_refresh' => $this->painel_tv_auto_refresh,
            'painel_tv_intervalo_segundos' => $this->painel_tv_intervalo_segundos,
            'notificacoes_ativas' => $this->notificacoes_ativas,
            'som_alerta_ativo' => $this->som_alerta_ativo,
            'modo_selecao_assistente' => $this->modo_selecao_assistente,
            'max_atendimentos_simultaneos_assistente' => $this->max_atendimentos_simultaneos_assistente,
            'ultima_assistente_sequencial_id' => $this->ultima_assistente_sequencial_id,
            'fonte_ordem_assistente' => $this->fonte_ordem_assistente,
            'reset_cursor_assistente' => $this->reset_cursor_assistente,
            'momento_atribuicao_assistente' => $this->momento_atribuicao_assistente,
            'cursor_sequencial_data' => $this->cursor_sequencial_data?->format('Y-m-d'),
            'status' => $this->status,
            'trinks_ativo' => (bool) $this->trinks_ativo,
            'trinks_estabelecimento_id' => $this->trinks_estabelecimento_id,
            'trinks_api_key_definida' => filled($this->trinks_api_key),
            'trinks_webhook_url' => url('/api/webhooks/trinks'),
        ];
    }
}
