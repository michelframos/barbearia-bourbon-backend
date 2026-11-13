<?php

namespace App\Modules\Usuarios\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UsuarioResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
