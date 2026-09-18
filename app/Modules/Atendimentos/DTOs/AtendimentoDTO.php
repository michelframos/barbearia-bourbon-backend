<?php

namespace App\Modules\Atendimentos\DTOs;

class AtendimentoDTO
{
    public function __construct(
        public readonly array $data
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self($data);
    }
}
