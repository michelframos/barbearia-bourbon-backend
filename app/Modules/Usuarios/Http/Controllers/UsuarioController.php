<?php

namespace App\Modules\Usuarios\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use App\Modules\Usuarios\Contracts\UsuarioRepositoryInterface;
use App\Modules\Usuarios\DTOs\UsuarioDTO;
use App\Modules\Usuarios\Http\Requests\StoreUsuarioRequest;
use App\Modules\Usuarios\Http\Requests\UpdateUsuarioRequest;
use App\Modules\Usuarios\Http\Resources\UsuarioResource;
use App\Modules\Usuarios\UseCases\CreateUsuarioUseCase;
use App\Modules\Usuarios\UseCases\DeleteUsuarioUseCase;
use App\Modules\Usuarios\UseCases\RestoreUsuarioUseCase;
use App\Modules\Usuarios\UseCases\UpdateUsuarioUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UsuarioController extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly UsuarioRepositoryInterface $repository,
        private readonly CreateUsuarioUseCase $createUseCase,
        private readonly UpdateUsuarioUseCase $updateUseCase,
        private readonly DeleteUsuarioUseCase $deleteUseCase,
        private readonly RestoreUsuarioUseCase $restoreUseCase,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $idEmpresa = $this->resolveIdEmpresa($request);

        $results = $this->repository->paginate(
            idEmpresa: $idEmpresa,
            search: (string) $request->get('search', ''),
            perPage: (int) $request->get('per_page', 15)
        );

        return UsuarioResource::collection($results);
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idEmpresa = $this->resolveIdEmpresa($request);
        $data['id_empresa'] = $idEmpresa;

        $model = $this->createUseCase->execute(UsuarioDTO::fromRequest($data));

        return response()->json([
            'message' => 'Usuário criado com sucesso.',
            'data' => new UsuarioResource($model),
        ], 201);
    }

    public function show(Request $request, string $id): UsuarioResource
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        return new UsuarioResource($model);
    }

    public function update(UpdateUsuarioRequest $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);
        $updated = $this->updateUseCase->execute($model, UsuarioDTO::fromRequest($request->validated()));

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'data' => new UsuarioResource($updated),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $this->deleteUseCase->execute(
            $idEmpresa,
            $id,
            $request->user()?->id !== null ? (int) $request->user()->id : null
        );

        return response()->json([
            'message' => 'Usuário removido com sucesso.',
        ]);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->restoreUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Usuário restaurado com sucesso.',
            'data' => new UsuarioResource($model),
        ]);
    }
}
