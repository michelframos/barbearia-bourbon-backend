<?php

namespace App\Modules\Agendamentos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agendamentos\Contracts\AgendamentoRepositoryInterface;
use App\Modules\Agendamentos\DTOs\AgendamentoDTO;
use App\Modules\Agendamentos\Http\Requests\StoreAgendamentoRequest;
use App\Modules\Agendamentos\Http\Requests\UpdateAgendamentoRequest;
use App\Modules\Agendamentos\Http\Resources\AgendamentoResource;
use App\Modules\Agendamentos\UseCases\CancelAgendamentoUseCase;
use App\Modules\Agendamentos\UseCases\CreateAgendamentoUseCase;
use App\Modules\Agendamentos\UseCases\MontarGradeAgendaUseCase;
use App\Modules\Agendamentos\UseCases\RestoreAgendamentoUseCase;
use App\Modules\Agendamentos\UseCases\UpdateAgendamentoUseCase;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgendamentoController extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly AgendamentoRepositoryInterface $repository,
        private readonly CreateAgendamentoUseCase $createUseCase,
        private readonly UpdateAgendamentoUseCase $updateUseCase,
        private readonly CancelAgendamentoUseCase $cancelUseCase,
        private readonly RestoreAgendamentoUseCase $restoreUseCase,
        private readonly MontarGradeAgendaUseCase $gradeUseCase,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $data = (string) $request->validate([
            'data' => ['required', 'date_format:Y-m-d'],
            'id_barbeiro' => ['nullable', 'integer'],
        ])['data'];

        $idBarbeiro = $request->filled('id_barbeiro')
            ? (int) $request->get('id_barbeiro')
            : null;

        $results = $this->repository->listByDate($idEmpresa, $data, $idBarbeiro);

        return AgendamentoResource::collection($results);
    }

    public function grade(Request $request): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $validated = $request->validate([
            'data' => ['required', 'date_format:Y-m-d'],
            'id_barbeiro' => ['nullable', 'integer'],
        ]);

        $grade = $this->gradeUseCase->execute(
            $idEmpresa,
            $validated['data'],
            isset($validated['id_barbeiro']) ? (int) $validated['id_barbeiro'] : null
        );

        return response()->json($grade);
    }

    public function store(StoreAgendamentoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idEmpresa = $this->resolveIdEmpresa(
            $request,
            isset($data['id_empresa']) ? (int) $data['id_empresa'] : null
        );
        $data['id_empresa'] = $idEmpresa;

        $dto = AgendamentoDTO::fromRequest($data);
        $model = $this->createUseCase->execute($dto);

        return response()->json([
            'message' => 'Agendamento criado com sucesso.',
            'data' => new AgendamentoResource($model),
        ], 201);
    }

    public function show(Request $request, string $id): AgendamentoResource
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        return new AgendamentoResource($model);
    }

    public function update(UpdateAgendamentoRequest $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);
        $dto = AgendamentoDTO::fromRequest($request->validated());
        $updated = $this->updateUseCase->execute($model, $dto);

        return response()->json([
            'message' => 'Agendamento atualizado com sucesso.',
            'data' => new AgendamentoResource($updated),
        ]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->cancelUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Agendamento cancelado com sucesso.',
            'data' => new AgendamentoResource($model),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $this->cancelUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Agendamento removido com sucesso.',
        ]);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->restoreUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Agendamento restaurado com sucesso.',
            'data' => new AgendamentoResource($model),
        ]);
    }
}
