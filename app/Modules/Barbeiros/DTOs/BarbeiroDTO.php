<?php

namespace App\Modules\Barbeiros\DTOs;

class BarbeiroDTO
{
    public function __construct(
        public readonly array $data
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self($data);
    }
}