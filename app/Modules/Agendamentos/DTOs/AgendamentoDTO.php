<?php

namespace App\Modules\Agendamentos\DTOs;

class AgendamentoDTO
{
    public function __construct(
        public readonly array $data
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self($data);
    }
}