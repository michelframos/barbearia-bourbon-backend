<?php

namespace App\Modules\Core\Adapters;

use App\Modules\Core\Contracts\NotificadorInterface;
use Illuminate\Support\Facades\Log;

class LogNotificadorAdapter implements NotificadorInterface
{
    /**
     * @param  array<string, mixed>  $contexto
     */
    public function enviar(string $destino, string $mensagem, array $contexto = []): void
    {
        Log::info('[Notificador] '.$mensagem, [
            'destino' => $destino,
            ...$contexto,
        ]);
    }
}
