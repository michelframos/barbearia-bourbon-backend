<?php

namespace App\Modules\Atendimentos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Atendimentos\Contracts\AtendimentoRepositoryInterface;
use App\Modules\Atendimentos\Contracts\PainelTvSnapshotProxyInterface;
use App\Modules\Atendimentos\DTOs\AtendimentoDTO;
use App\Modules\Atendimentos\Http\Requests\CheckInAtendimentoRequest;
use App\Modules\Atendimentos\Http\Requests\IniciarAtendimentoRequest;
use App\Modules\Atendimentos\Http\Requests\ListAtendimentosRequest;
use App\Modules\Atendimentos\Http\Resources\AtendimentoResource;
use App\Modules\Atendimentos\UseCases\CancelarAtendimentoUseCase;
use App\Modules\Atendimentos\UseCases\ChamarAtendimentoUseCase;
use App\Modules\Atendimentos\UseCases\CheckInAtendimentoUseCase;
use App\Modules\Atendimentos\UseCases\ConcluirAtendimentoUseCase;
use App\Modules\Atendimentos\UseCases\IniciarAtendimentoUseCase;
use App\Modules\Atendimentos\UseCases\MontarPainelOperacionalUseCase;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AtendimentoController extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly AtendimentoRepositoryInterface $repository,
        private readonly CheckInAtendimentoUseCase $checkInUseCase,
        private readonly ChamarAtendimentoUseCase $chamarUseCase,
        private readonly IniciarAtendimentoUseCase $iniciarUseCase,
        private readonly ConcluirAtendimentoUseCase $concluirUseCase,
        private readonly CancelarAtendimentoUseCase $cancelarUseCase,
        private readonly MontarPainelOperacionalUseCase $painelUseCase,
        private readonly PainelTvSnapshotProxyInterface $painelTvProxy,
    ) {}

    public function index(ListAtendimentosRequest $request): AnonymousResourceCollection
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $validated = $request->validated();

        $results = $this->repository->listByDate(
            $idEmpresa,
            $validated['data'],
            $validated['status_atendimento'] ?? null
        );

        return AtendimentoResource::collection($results);
    }

    public function painel(Request $request): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $validated = $request->validate([
            'data' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $data = $validated['data'] ?? now()->toDateString();

        return response()->json(
            $this->painelUseCase->execute($idEmpresa, $data)
        );
    }

    public function painelTv(Request $request): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $validated = $request->validate([
            'data' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $data = $validated['data'] ?? now()->toDateString();

        return response()->json(
            $this->painelTvProxy->obterSnapshot($idEmpresa, $data)
        );
    }

    public function checkIn(CheckInAtendimentoRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['id_empresa'] = $this->resolveIdEmpresa($request);

        $model = $this->checkInUseCase->execute(AtendimentoDTO::fromRequest($payload));

        return response()->json([
            'message' => 'Check-in realizado com sucesso.',
            'data' => new AtendimentoResource($model),
        ], 201);
    }

    public function show(Request $request, string $id): AtendimentoResource
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        return new AtendimentoResource($model);
    }

    public function chamar(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->chamarUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Cliente chamado.',
            'data' => new AtendimentoResource($model),
        ]);
    }

    public function iniciar(IniciarAtendimentoRequest $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $validated = $request->validated();
        $model = $this->iniciarUseCase->execute(
            $idEmpresa,
            $id,
            isset($validated['id_barbeiro']) ? (int) $validated['id_barbeiro'] : null,
            isset($validated['id_assistente']) ? (int) $validated['id_assistente'] : null,
        );

        return response()->json([
            'message' => 'Atendimento iniciado.',
            'data' => new AtendimentoResource($model),
        ]);
    }

    public function concluir(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->concluirUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Atendimento concluído.',
            'data' => new AtendimentoResource($model),
        ]);
    }

    public function cancelar(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->cancelarUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Atendimento cancelado.',
            'data' => new AtendimentoResource($model),
        ]);
    }
}
