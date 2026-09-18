<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function registrar(
        string $acao,
        string $entidade,
        ?int $idEntidade,
        string $descricao,
        ?array $payload = null,
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'id_empresa' => EmpresaContext::id(),
            'id_user' => $userId ?? Auth::id(),
            'acao' => $acao,
            'entidade' => $entidade,
            'id_entidade' => $idEntidade,
            'descricao' => mb_substr($descricao, 0, 500),
            'payload' => $payload,
        ]);
    }
}
