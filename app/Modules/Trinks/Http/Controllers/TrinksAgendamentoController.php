<?php

namespace App\Modules\Trinks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\EmpresaConfigService;
use App\Modules\Trinks\UseCases\ExportarAgendamentosLocaisUseCase;
use App\Modules\Trinks\UseCases\ImportarAgendamentosTrinksUseCase;
use Illuminate\Http\JsonResponse;

class TrinksAgendamentoController extends Controller
{
    public function __construct(
        private readonly EmpresaConfigService $empresaConfig,
        private readonly ImportarAgendamentosTrinksUseCase $importar,
        private readonly ExportarAgendamentosLocaisUseCase $exportar,
    ) {}

    public function importar(): JsonResponse
    {
        return response()->json($this->importar->execute($this->empresaConfig->getEmpresa()));
    }

    public function exportar(): JsonResponse
    {
        return response()->json($this->exportar->execute($this->empresaConfig->getEmpresa()));
    }
}
