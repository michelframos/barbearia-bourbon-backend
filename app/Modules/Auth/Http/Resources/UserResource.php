<?php

namespace App\Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_empresa' => $this->id_empresa,
            'name' => $this->name,
            'email' => $this->email,
            'perfil' => $this->perfil,
            'id_assistente' => $this->id_assistente,
            'id_barbeiro' => $this->id_barbeiro,
        ];
    }
}
