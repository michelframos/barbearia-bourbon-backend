<?php

namespace App\Modules\Servicos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Servicos\Models\ServicoModel
 */
class ServicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_empresa' => $this->id_empresa,
            'nome' => $this->nome,
            'duracao_minutos' => $this->duracao_minutos,
            'preco' => $this->preco,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
