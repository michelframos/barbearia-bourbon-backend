<?php

namespace App\Modules\Assistentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use App\Modules\Assistentes\Contracts\AssistenteRepositoryInterface;
use App\Modules\Assistentes\DTOs\AssistenteDTO;
use App\Modules\Assistentes\Http\Requests\StoreAssistenteRequest;
use App\Modules\Assistentes\Http\Requests\UpdateAssistenteRequest;
use App\Modules\Assistentes\Http\Resources\AssistenteResource;
use App\Modules\Assistentes\UseCases\CreateAssistenteUseCase;
use App\Modules\Assistentes\UseCases\DeleteAssistenteUseCase;
use App\Modules\Assistentes\UseCases\RestoreAssistenteUseCase;
use App\Modules\Assistentes\UseCases\UpdateAssistenteUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssistenteController extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly AssistenteRepositoryInterface $repository,
        private readonly CreateAssistenteUseCase $createUseCase,
        private readonly UpdateAssistenteUseCase $updateUseCase,
        private readonly DeleteAssistenteUseCase $deleteUseCase,
        private readonly RestoreAssistenteUseCase $restoreUseCase,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $idEmpresa = $this->resolveIdEmpresa($request);

        $results = $this->repository->paginate(
            idEmpresa: $idEmpresa,
            search: $request->get('search', ''),
            perPage: (int) $request->get('per_page', 15)
        );

        return AssistenteResource::collection($results);
    }

    public function store(StoreAssistenteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idEmpresa = $this->resolveIdEmpresa(
            $request,
            isset($data['id_empresa']) ? (int) $data['id_empresa'] : null
        );
        $data['id_empresa'] = $idEmpresa;
        $data['status_operacional'] = $data['status_operacional'] ?? 'offline';
        $data['status'] = $data['status'] ?? 'a';

        $dto = AssistenteDTO::fromRequest($data);
        $model = $this->createUseCase->execute($dto);

        return response()->json([
            'message' => 'Assistente criado com sucesso.',
            'data' => new AssistenteResource($model),
        ], 201);
    }

    public function show(Request $request, string $id): AssistenteResource
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        return new AssistenteResource($model);
    }

    public function update(UpdateAssistenteRequest $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);
        $dto = AssistenteDTO::fromRequest($request->validated());
        $updated = $this->updateUseCase->execute($model, $dto);

        return response()->json([
            'message' => 'Assistente atualizado com sucesso.',
            'data' => new AssistenteResource($updated),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $this->deleteUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Assistente removido com sucesso.',
        ]);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->restoreUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Assistente restaurado com sucesso.',
            'data' => new AssistenteResource($model),
        ]);
    }
}