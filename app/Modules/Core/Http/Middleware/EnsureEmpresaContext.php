<?php

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Support\EmpresaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Garante o contexto da empresa única da instalação (alias: empresa).
 */
class EnsureEmpresaContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $fixed = EmpresaContext::id();

        $user = $request->user();

        if ($user !== null && isset($user->id_empresa) && (int) $user->id_empresa !== $fixed) {
            throw new AccessDeniedHttpException('Acesso negado à empresa da instalação.');
        }

        $request->attributes->set('id_empresa', $fixed);

        return $next($request);
    }
}
