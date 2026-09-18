<?php

namespace App\Modules\Core\Contracts;

interface NotificadorInterface
{
    /**
     * @param  array<string, mixed>  $contexto
     */
    public function enviar(string $destino, string $mensagem, array $contexto = []): void;
}
