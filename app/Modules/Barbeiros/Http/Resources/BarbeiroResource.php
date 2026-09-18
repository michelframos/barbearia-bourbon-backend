<?php

namespace App\Modules\Barbeiros\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Barbeiros\Models\BarbeiroModel
 */
class BarbeiroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $servicos = $this->whenLoaded('servicos', function () {
            return $this->servicos->map(fn ($servico) => [
                'id' => $servico->id,
                'nome' => $servico->nome,
                'duracao_minutos' => $servico->duracao_minutos,
                'preco' => $servico->preco,
                'status' => $servico->status,
            ])->values();
        });

        $atendimentoAtual = $this->when($this->relationLoaded('atendimentoAtual'), function () {
            $atendimento = $this->atendimentoAtual;
            if (! $atendimento) {
                return null;
            }

            return [
                'id' => $atendimento->id,
                'hora_inicio' => $atendimento->hora_inicio
                    ? substr((string) $atendimento->hora_inicio, 0, 5)
                    : null,
                'cliente' => $atendimento->cliente ? [
                    'id' => $atendimento->cliente->id,
                    'nome' => $atendimento->cliente->nome,
                ] : null,
                'servico' => $atendimento->servico ? [
                    'id' => $atendimento->servico->id,
                    'nome' => $atendimento->servico->nome,
                ] : null,
            ];
        });

        return [
            'id' => $this->id,
            'id_empresa' => $this->id_empresa,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'foto' => $this->foto,
            'foto_url' => $this->foto ? '/storage/'.$this->foto : null,
            'status_operacional' => $this->status_operacional,
            'hora_entrada' => $this->hora_entrada
                ? substr((string) $this->hora_entrada, 0, 5)
                : null,
            'status' => $this->status,
            'atendimento_atual' => $atendimentoAtual,
            'servicos' => $servicos,
            'servico_ids' => $this->whenLoaded(
                'servicos',
                fn () => $this->servicos->pluck('id')->values()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
