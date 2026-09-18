<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use App\Modules\Dashboard\UseCases\MontarDashboardHojeUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly MontarDashboardHojeUseCase $dashboardUseCase,
    ) {}

    public function hoje(Request $request): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $validated = $request->validate([
            'data' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $data = $validated['data'] ?? now()->toDateString();

        return response()->json(
            $this->dashboardUseCase->execute($idEmpresa, $data)
        );
    }
}
