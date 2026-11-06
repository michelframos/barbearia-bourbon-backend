<?php

namespace App\Modules\Trinks\Contracts;

interface VerifiesTrinksSnsSignature
{
    /**
     * @param  array<string, mixed>  $envelope
     */
    public function assertValid(array $envelope): void;
}
