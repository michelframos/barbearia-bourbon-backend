<?php

namespace App\Modules\Clientes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clientes\Contracts\ClienteRepositoryInterface;
use App\Modules\Clientes\DTOs\ClienteDTO;
use App\Modules\Clientes\Http\Requests\StoreClienteRequest;
use App\Modules\Clientes\Http\Requests\UpdateClienteRequest;
use App\Modules\Clientes\Http\Resources\ClienteResource;
use App\Modules\Clientes\UseCases\CreateClienteUseCase;
use App\Modules\Clientes\UseCases\DeleteClienteUseCase;
use App\Modules\Clientes\UseCases\RestoreClienteUseCase;
use App\Modules\Clientes\UseCases\UpdateClienteUseCase;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClienteController extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly ClienteRepositoryInterface $repository,
        private readonly CreateClienteUseCase $createUseCase,
        private readonly UpdateClienteUseCase $updateUseCase,
        private readonly DeleteClienteUseCase $deleteUseCase,
        private readonly RestoreClienteUseCase $restoreUseCase,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $idEmpresa = $this->resolveIdEmpresa($request);

        $results = $this->repository->paginate(
            idEmpresa: $idEmpresa,
            search: $request->get('search', ''),
            perPage: (int) $request->get('per_page', 15)
        );

        return ClienteResource::collection($results);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idEmpresa = $this->resolveIdEmpresa(
            $request,
            isset($data['id_empresa']) ? (int) $data['id_empresa'] : null
        );
        $data['id_empresa'] = $idEmpresa;
        $data['status'] = $data['status'] ?? 'a';

        $dto = ClienteDTO::fromRequest($data);
        $model = $this->createUseCase->execute($dto);

        return response()->json([
            'message' => 'Cliente criado com sucesso.',
            'data' => new ClienteResource($model),
        ], 201);
    }

    public function show(Request $request, string $id): ClienteResource
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        return new ClienteResource($model);
    }

    public function update(UpdateClienteRequest $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);
        $dto = ClienteDTO::fromRequest($request->validated());
        $updated = $this->updateUseCase->execute($model, $dto);

        return response()->json([
            'message' => 'Cliente atualizado com sucesso.',
            'data' => new ClienteResource($updated),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $this->deleteUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Cliente removido com sucesso.',
        ]);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->restoreUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Cliente restaurado com sucesso.',
            'data' => new ClienteResource($model),
        ]);
    }
}
