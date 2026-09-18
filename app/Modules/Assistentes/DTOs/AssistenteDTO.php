<?php

namespace App\Modules\Assistentes\DTOs;

class AssistenteDTO
{
    public function __construct(
        public readonly array $data
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self($data);
    }
}