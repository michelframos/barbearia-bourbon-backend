<?php

namespace App\Modules\Atendimentos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Atendimentos\Models\AtendimentoModel
 */
class AtendimentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_empresa' => $this->id_empresa,
            'id_agendamento' => $this->id_agendamento,
            'id_cliente' => $this->id_cliente,
            'id_assistente' => $this->id_assistente,
            'id_barbeiro' => $this->id_barbeiro,
            'id_servico' => $this->id_servico,
            'posicao_fila' => $this->posicao_fila,
            'data' => $this->data?->format('Y-m-d'),
            'hora_chegada' => $this->hora_chegada ? substr((string) $this->hora_chegada, 0, 5) : null,
            'hora_inicio' => $this->hora_inicio ? substr((string) $this->hora_inicio, 0, 5) : null,
            'hora_fim' => $this->hora_fim ? substr((string) $this->hora_fim, 0, 5) : null,
            'status_atendimento' => $this->status_atendimento,
            'observacoes' => $this->observacoes,
            'status' => $this->status,
            'cliente' => $this->whenLoaded('cliente', function () {
                if (! $this->cliente) {
                    return null;
                }

                return [
                    'id' => $this->cliente->id,
                    'nome' => $this->cliente->nome,
                    'telefone' => $this->cliente->telefone,
                ];
            }),
            'assistente' => $this->whenLoaded('assistente', function () {
                if (! $this->assistente) {
                    return null;
                }

                return [
                    'id' => $this->assistente->id,
                    'nome' => $this->assistente->nome,
                ];
            }),
            'barbeiro' => $this->whenLoaded('barbeiro', function () {
                if (! $this->barbeiro) {
                    return null;
                }

                return [
                    'id' => $this->barbeiro->id,
                    'nome' => $this->barbeiro->nome,
                ];
            }),
            'servico' => $this->whenLoaded('servico', function () {
                if (! $this->servico) {
                    return null;
                }

                return [
                    'id' => $this->servico->id,
                    'nome' => $this->servico->nome,
                    'duracao_minutos' => $this->servico->duracao_minutos,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
