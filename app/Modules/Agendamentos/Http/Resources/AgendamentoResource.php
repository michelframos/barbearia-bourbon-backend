<?php

namespace App\Modules\Agendamentos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Agendamentos\Models\AgendamentoModel
 */
class AgendamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hora = $this->hora
            ? substr((string) $this->hora, 0, 5)
            : null;

        return [
            'id' => $this->id,
            'id_empresa' => $this->id_empresa,
            'data' => $this->data?->format('Y-m-d'),
            'hora' => $hora,
            'id_cliente' => $this->id_cliente,
            'id_assistente' => $this->id_assistente,
            'id_barbeiro' => $this->id_barbeiro,
            'id_servico' => $this->id_servico,
            'observacoes' => $this->observacoes,
            'status_agenda' => $this->status_agenda,
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
                    'preco' => $this->servico->preco,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
