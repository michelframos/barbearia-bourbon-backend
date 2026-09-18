<?php

namespace App\Modules\Assistentes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Assistentes\Models\AssistenteModel
 */
class AssistenteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_empresa' => $this->id_empresa,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'turno' => $this->turno,
            'ordem_sequencial' => $this->ordem_sequencial,
            'hora_checkin' => $this->hora_checkin?->toIso8601String(),
            'status_operacional' => $this->status_operacional,
            'status' => $this->status,
            'vinculos_barbeiro_dia' => $this->vinculosBarbeiroDia(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
