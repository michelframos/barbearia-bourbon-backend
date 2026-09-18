<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\UpdateEmpresaRequest;
use App\Modules\Core\Http\Resources\EmpresaResource;
use App\Modules\Core\Services\EmpresaConfigService;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use App\Modules\Core\UseCases\UpdateEmpresaUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly EmpresaConfigService $empresaConfig,
        private readonly UpdateEmpresaUseCase $updateUseCase,
    ) {}

    public function show(Request $request): EmpresaResource
    {
        $this->resolveIdEmpresa($request);

        return new EmpresaResource($this->empresaConfig->getEmpresa());
    }

    public function update(UpdateEmpresaRequest $request): JsonResponse
    {
        $this->resolveIdEmpresa($request);
        $empresa = $this->updateUseCase->execute($request->validated());

        return response()->json([
            'message' => 'Configurações salvas com sucesso.',
            'data' => new EmpresaResource($empresa),
        ]);
    }
}
