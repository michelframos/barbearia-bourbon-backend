<?php

namespace App\Modules\Servicos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use App\Modules\Servicos\Contracts\ServicoRepositoryInterface;
use App\Modules\Servicos\DTOs\ServicoDTO;
use App\Modules\Servicos\Http\Requests\StoreServicoRequest;
use App\Modules\Servicos\Http\Requests\UpdateServicoRequest;
use App\Modules\Servicos\Http\Resources\ServicoResource;
use App\Modules\Servicos\UseCases\CreateServicoUseCase;
use App\Modules\Servicos\UseCases\DeleteServicoUseCase;
use App\Modules\Servicos\UseCases\RestoreServicoUseCase;
use App\Modules\Servicos\UseCases\UpdateServicoUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServicoController extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly ServicoRepositoryInterface $repository,
        private readonly CreateServicoUseCase $createUseCase,
        private readonly UpdateServicoUseCase $updateUseCase,
        private readonly DeleteServicoUseCase $deleteUseCase,
        private readonly RestoreServicoUseCase $restoreUseCase,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $idEmpresa = $this->resolveIdEmpresa($request);

        $results = $this->repository->paginate(
            idEmpresa: $idEmpresa,
            search: $request->get('search', ''),
            perPage: (int) $request->get('per_page', 15)
        );

        return ServicoResource::collection($results);
    }

    public function store(StoreServicoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idEmpresa = $this->resolveIdEmpresa(
            $request,
            isset($data['id_empresa']) ? (int) $data['id_empresa'] : null
        );
        $data['id_empresa'] = $idEmpresa;
        $data['duracao_minutos'] = $data['duracao_minutos'] ?? 30;
        $data['status'] = $data['status'] ?? 'a';

        $dto = ServicoDTO::fromRequest($data);
        $model = $this->createUseCase->execute($dto);

        return response()->json([
            'message' => 'Serviço criado com sucesso.',
            'data' => new ServicoResource($model),
        ], 201);
    }

    public function show(Request $request, string $id): ServicoResource
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        return new ServicoResource($model);
    }

    public function update(UpdateServicoRequest $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);
        $dto = ServicoDTO::fromRequest($request->validated());
        $updated = $this->updateUseCase->execute($model, $dto);

        return response()->json([
            'message' => 'Serviço atualizado com sucesso.',
            'data' => new ServicoResource($updated),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $this->deleteUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Serviço removido com sucesso.',
        ]);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->restoreUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Serviço restaurado com sucesso.',
            'data' => new ServicoResource($model),
        ]);
    }
}