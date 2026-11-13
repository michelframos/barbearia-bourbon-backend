<?php

namespace App\Modules\Usuarios\DTOs;

class UsuarioDTO
{
    public function __construct(
        public readonly array $data
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self($data);
    }
}
