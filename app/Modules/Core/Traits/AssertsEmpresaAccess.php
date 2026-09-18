<?php

namespace App\Modules\Core\Traits;

use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Resolve e valida id_empresa fixo nos controllers gerados pelo make:module.
 */
trait AssertsEmpresaAccess
{
    protected function resolveIdEmpresa(Request $request, ?int $requestedIdEmpresa = null): int
    {
        $fixed = EmpresaContext::id();

        $user = $request->user();

        if ($user !== null && isset($user->id_empresa) && (int) $user->id_empresa !== $fixed) {
            throw new AccessDeniedHttpException('Usuário não pertence à empresa da instalação.');
        }

        if ($requestedIdEmpresa !== null && $requestedIdEmpresa !== $fixed) {
            throw new AccessDeniedHttpException('id_empresa inválido para esta instalação.');
        }

        return $fixed;
    }
}
