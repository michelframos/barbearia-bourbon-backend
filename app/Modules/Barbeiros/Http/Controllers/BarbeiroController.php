<?php

namespace App\Modules\Barbeiros\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Barbeiros\Contracts\BarbeiroRepositoryInterface;
use App\Modules\Barbeiros\DTOs\BarbeiroDTO;
use App\Modules\Barbeiros\Http\Requests\StoreBarbeiroRequest;
use App\Modules\Barbeiros\Http\Requests\UpdateBarbeiroRequest;
use App\Modules\Barbeiros\Http\Requests\UploadBarbeiroFotoRequest;
use App\Modules\Barbeiros\Http\Resources\BarbeiroResource;
use App\Modules\Barbeiros\UseCases\CreateBarbeiroUseCase;
use App\Modules\Barbeiros\UseCases\DeleteBarbeiroUseCase;
use App\Modules\Barbeiros\UseCases\RestoreBarbeiroUseCase;
use App\Modules\Barbeiros\UseCases\UpdateBarbeiroUseCase;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class BarbeiroController extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly BarbeiroRepositoryInterface $repository,
        private readonly CreateBarbeiroUseCase $createUseCase,
        private readonly UpdateBarbeiroUseCase $updateUseCase,
        private readonly DeleteBarbeiroUseCase $deleteUseCase,
        private readonly RestoreBarbeiroUseCase $restoreUseCase,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $idEmpresa = $this->resolveIdEmpresa($request);

        $results = $this->repository->paginate(
            idEmpresa: $idEmpresa,
            search: $request->get('search', ''),
            perPage: (int) $request->get('per_page', 15)
        );

        return BarbeiroResource::collection($results);
    }

    public function store(StoreBarbeiroRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idEmpresa = $this->resolveIdEmpresa(
            $request,
            isset($data['id_empresa']) ? (int) $data['id_empresa'] : null
        );
        $data['id_empresa'] = $idEmpresa;
        $data['status_operacional'] = $data['status_operacional'] ?? 'offline';
        $data['status'] = $data['status'] ?? 'a';
        $data['servico_ids'] = $data['servico_ids'] ?? [];

        $dto = BarbeiroDTO::fromRequest($data);
        $model = $this->createUseCase->execute($dto);

        return response()->json([
            'message' => 'Barbeiro criado com sucesso.',
            'data' => new BarbeiroResource($model),
        ], 201);
    }

    public function show(Request $request, string $id): BarbeiroResource
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        return new BarbeiroResource($model);
    }

    public function update(UpdateBarbeiroRequest $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);
        $dto = BarbeiroDTO::fromRequest($request->validated());
        $updated = $this->updateUseCase->execute($model, $dto);

        return response()->json([
            'message' => 'Barbeiro atualizado com sucesso.',
            'data' => new BarbeiroResource($updated),
        ]);
    }

    public function uploadFoto(UploadBarbeiroFotoRequest $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        if ($model->foto) {
            Storage::disk('public')->delete($model->foto);
        }

        $path = $request->file('foto')->store(
            "barbeiros/{$idEmpresa}/{$model->id}",
            'public'
        );

        $updated = $this->repository->updateFoto($model, $path);

        return response()->json([
            'message' => 'Foto atualizada com sucesso.',
            'data' => new BarbeiroResource($updated),
        ]);
    }

    public function destroyFoto(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        if ($model->foto) {
            Storage::disk('public')->delete($model->foto);
        }

        $updated = $this->repository->clearFoto($model);

        return response()->json([
            'message' => 'Foto removida com sucesso.',
            'data' => new BarbeiroResource($updated),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        if ($model->foto) {
            Storage::disk('public')->delete($model->foto);
        }

        $this->deleteUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Barbeiro removido com sucesso.',
        ]);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->restoreUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => 'Barbeiro restaurado com sucesso.',
            'data' => new BarbeiroResource($model),
        ]);
    }
}
