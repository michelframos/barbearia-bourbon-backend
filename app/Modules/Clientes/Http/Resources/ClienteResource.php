<?php

namespace App\Modules\Clientes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Clientes\Models\ClienteModel
 */
class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_empresa' => $this->id_empresa,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'id_assistente_preferida' => $this->id_assistente_preferida,
            'assistente_preferida' => $this->whenLoaded('assistentePreferida', function () {
                if (! $this->assistentePreferida) {
                    return null;
                }

                return [
                    'id' => $this->assistentePreferida->id,
                    'nome' => $this->assistentePreferida->nome,
                ];
            }),
            'observacoes' => $this->observacoes,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
