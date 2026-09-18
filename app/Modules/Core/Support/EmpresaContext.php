<?php

namespace App\Modules\Core\Support;

/**
 * Resolução do id_empresa fixo da instalação (single-company).
 */
final class EmpresaContext
{
    public static function id(): int
    {
        return (int) config('empresa.id', 1);
    }
}
