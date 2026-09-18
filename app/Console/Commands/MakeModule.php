<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    protected $signature = 'make:module {name : O nome do módulo (ex: Produtos)}';

    protected $description = 'Cria um novo módulo estruturado seguindo Arquitetura Limpa (id_empresa + SoftDeletes + schema PT).';

    public function handle(): int
    {
        $nameInput = $this->argument('name');

        $moduleName = Str::studly($nameInput);
        $singular = Str::studly(Str::singular($nameInput));
        $plural = Str::studly(Str::plural($nameInput));
        $table = Str::snake($plural);
        $routePrefix = Str::kebab($plural);
        $variable = Str::camel($singular);

        $modulePath = app_path("Modules/{$moduleName}");

        if (File::exists($modulePath)) {
            $this->error("O módulo [{$moduleName}] já existe!");

            return Command::FAILURE;
        }

        $this->info("Criando a estrutura para o módulo: {$moduleName}...");

        $directories = [
            'Contracts',
            'DTOs',
            'Http/Controllers',
            'Http/Requests',
            'Http/Resources',
            'Models',
            'Observers',
            'Providers',
            'Repositories',
            'UseCases',
        ];

        foreach ($directories as $dir) {
            File::makeDirectory("{$modulePath}/{$dir}", 0755, true);
        }

        $context = compact('moduleName', 'singular', 'plural', 'table', 'routePrefix', 'variable');

        $this->generateContracts($modulePath, $context);
        $this->generateDTOs($modulePath, $context);
        $this->generateModels($modulePath, $context);
        $this->generateObservers($modulePath, $context);
        $this->generateRepositories($modulePath, $context);
        $this->generateProviders($modulePath, $context);
        $this->generateRoutes($modulePath, $context);
        $this->generateControllers($modulePath, $context);
        $this->generateRequests($modulePath, $context);
        $this->generateResources($modulePath, $context);
        $this->generateUseCases($modulePath, $context);
        $this->generateMigration($context);

        $this->registerServiceProvider($moduleName, $singular);

        $this->info("Módulo [{$moduleName}] gerado com sucesso em app/Modules/{$moduleName}!");
        $this->comment('Schema: TenantTable (PK id_empresa+id). Documente endpoints no Swagger.');
        $this->comment('Partição: MYSQL_PARTITIONING_ENABLED=true → PARTITION BY KEY no CREATE.');

        return Command::SUCCESS;
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function replacePlaceholders(string $content, array $ctx): string
    {
        return str_replace(
            ['{{Module}}', '{{Singular}}', '{{Plural}}', '{{table}}', '{{routePrefix}}', '{{variable}}'],
            [$ctx['moduleName'], $ctx['singular'], $ctx['plural'], $ctx['table'], $ctx['routePrefix'], $ctx['variable']],
            $content
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateContracts(string $path, array $ctx): void
    {
        $content = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\Contracts;

use App\Modules\{{Module}}\DTOs\{{Singular}}DTO;
use App\Modules\{{Module}}\Models\{{Singular}}Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface {{Singular}}RepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $idEmpresa, string|int $id): {{Singular}}Model;

    public function create({{Singular}}DTO $dto): {{Singular}}Model;

    public function update({{Singular}}Model $model, {{Singular}}DTO $dto): {{Singular}}Model;

    public function delete({{Singular}}Model $model): void;

    public function restore(int $idEmpresa, string|int $id): {{Singular}}Model;
}
PHP;

        File::put(
            "{$path}/Contracts/{$ctx['singular']}RepositoryInterface.php",
            $this->replacePlaceholders($content, $ctx)
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateDTOs(string $path, array $ctx): void
    {
        $content = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\DTOs;

class {{Singular}}DTO
{
    public function __construct(
        public readonly array $data
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self($data);
    }
}
PHP;

        File::put(
            "{$path}/DTOs/{$ctx['singular']}DTO.php",
            $this->replacePlaceholders($content, $ctx)
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateModels(string $path, array $ctx): void
    {
        $content = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\Models;

use App\Modules\Core\Traits\AssignsMySqlAutoIncrementId;
use App\Modules\Core\Traits\BelongsToEmpresa;
use App\Modules\Core\Traits\HasEmpresaCompositeKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {{Singular}}Model extends Model
{
    use AssignsMySqlAutoIncrementId;
    use BelongsToEmpresa;
    use HasEmpresaCompositeKey;
    use HasFactory;
    use SoftDeletes;

    protected $table = '{{table}}';

    /** @var array<int, string> */
    protected $primaryKey = ['id_empresa', 'id'];

    public $incrementing = false;

    protected $fillable = [
        'id_empresa',
        'id',
        // Colunas em português (compatível com o legado)
    ];
}
PHP;

        File::put(
            "{$path}/Models/{$ctx['singular']}Model.php",
            $this->replacePlaceholders($content, $ctx)
        );
    }

    /**
     * Migration tenant: PK composta + soft deletes + partição (se enabled).
     *
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateMigration(array $ctx): void
    {
        $timestamp = now()->format('Y_m_d_His');
        $table = $ctx['table'];
        $path = database_path("migrations/{$timestamp}_create_{$table}_tenant_table.php");

        $content = <<<PHP
<?php

use App\Modules\Core\Database\TenantTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Tabela tenant [{$table}]: PK (id, id_empresa), soft deletes,
 * PARTITION BY KEY(id_empresa) quando MYSQL_PARTITIONING_ENABLED=true.
 */
return new class extends Migration
{
    public function up(): void
    {
        TenantTable::create('{$table}', function (Blueprint \$table): void {
            // Colunas de negócio em português
            \$table->string('nome', 255)->nullable();
            \$table->string('status', 1)->default('a');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        TenantTable::dropIfExists('{$table}');
    }
};
PHP;

        File::put($path, $content);
        $this->line("Migration tenant: {$path}");
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateObservers(string $path, array $ctx): void
    {
        $content = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\Observers;

use App\Modules\{{Module}}\Models\{{Singular}}Model;

class {{Singular}}Observer
{
    public function creating({{Singular}}Model ${{variable}}): void
    {
        //
    }
}
PHP;

        File::put(
            "{$path}/Observers/{$ctx['singular']}Observer.php",
            $this->replacePlaceholders($content, $ctx)
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateRepositories(string $path, array $ctx): void
    {
        $content = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\Repositories;

use App\Modules\{{Module}}\Contracts\{{Singular}}RepositoryInterface;
use App\Modules\{{Module}}\DTOs\{{Singular}}DTO;
use App\Modules\{{Module}}\Models\{{Singular}}Model;
use Illuminate\Pagination\LengthAwarePaginator;

class {{Singular}}Repository implements {{Singular}}RepositoryInterface
{
    public function paginate(int $idEmpresa, string $search, int $perPage = 15): LengthAwarePaginator
    {
        return {{Singular}}Model::query()
            ->where('id_empresa', $idEmpresa)
            ->when(
                filled($search),
                fn ($q) => $q->where('id', 'like', "%{$search}%")
            )
            ->with(['empresa'])
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $idEmpresa, string|int $id): {{Singular}}Model
    {
        return {{Singular}}Model::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function create({{Singular}}DTO $dto): {{Singular}}Model
    {
        return {{Singular}}Model::create($dto->data);
    }

    public function update({{Singular}}Model $model, {{Singular}}DTO $dto): {{Singular}}Model
    {
        $model->update($dto->data);

        return $model->fresh(['empresa']);
    }

    public function delete({{Singular}}Model $model): void
    {
        $model->delete(); // SoftDeletes
    }

    public function restore(int $idEmpresa, string|int $id): {{Singular}}Model
    {
        $model = {{Singular}}Model::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $id)
            ->firstOrFail();

        $model->restore();

        return $model->fresh(['empresa']);
    }
}
PHP;

        File::put(
            "{$path}/Repositories/{$ctx['singular']}Repository.php",
            $this->replacePlaceholders($content, $ctx)
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateProviders(string $path, array $ctx): void
    {
        $content = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\Providers;

use App\Modules\{{Module}}\Contracts\{{Singular}}RepositoryInterface;
use App\Modules\{{Module}}\Models\{{Singular}}Model;
use App\Modules\{{Module}}\Observers\{{Singular}}Observer;
use App\Modules\{{Module}}\Repositories\{{Singular}}Repository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class {{Singular}}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            {{Singular}}RepositoryInterface::class,
            {{Singular}}Repository::class,
        );
    }

    public function boot(): void
    {
        {{Singular}}Model::observe({{Singular}}Observer::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../routes.php');
    }
}
PHP;

        File::put(
            "{$path}/Providers/{$ctx['singular']}ServiceProvider.php",
            $this->replacePlaceholders($content, $ctx)
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateRoutes(string $path, array $ctx): void
    {
        $content = <<<'PHP'
<?php

use App\Modules\{{Module}}\Http\Controllers\{{Singular}}Controller;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'empresa'])->prefix('{{routePrefix}}')->group(function () {
    Route::get('/', [{{Singular}}Controller::class, 'index']);
    Route::post('/', [{{Singular}}Controller::class, 'store']);
    Route::get('/{id}', [{{Singular}}Controller::class, 'show']);
    Route::put('/{id}', [{{Singular}}Controller::class, 'update']);
    Route::delete('/{id}', [{{Singular}}Controller::class, 'destroy']);
    Route::post('/{id}/restore', [{{Singular}}Controller::class, 'restore']);
});
PHP;

        File::put(
            "{$path}/routes.php",
            $this->replacePlaceholders($content, $ctx)
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateControllers(string $path, array $ctx): void
    {
        $content = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\AssertsEmpresaAccess;
use App\Modules\{{Module}}\Contracts\{{Singular}}RepositoryInterface;
use App\Modules\{{Module}}\DTOs\{{Singular}}DTO;
use App\Modules\{{Module}}\Http\Requests\Store{{Singular}}Request;
use App\Modules\{{Module}}\Http\Requests\Update{{Singular}}Request;
use App\Modules\{{Module}}\Http\Resources\{{Singular}}Resource;
use App\Modules\{{Module}}\UseCases\Create{{Singular}}UseCase;
use App\Modules\{{Module}}\UseCases\Delete{{Singular}}UseCase;
use App\Modules\{{Module}}\UseCases\Restore{{Singular}}UseCase;
use App\Modules\{{Module}}\UseCases\Update{{Singular}}UseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class {{Singular}}Controller extends Controller
{
    use AssertsEmpresaAccess;

    public function __construct(
        private readonly {{Singular}}RepositoryInterface $repository,
        private readonly Create{{Singular}}UseCase $createUseCase,
        private readonly Update{{Singular}}UseCase $updateUseCase,
        private readonly Delete{{Singular}}UseCase $deleteUseCase,
        private readonly Restore{{Singular}}UseCase $restoreUseCase,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $idEmpresa = $this->resolveIdEmpresa($request);

        $results = $this->repository->paginate(
            idEmpresa: $idEmpresa,
            search: $request->get('search', ''),
            perPage: (int) $request->get('per_page', 15)
        );

        return {{Singular}}Resource::collection($results);
    }

    public function store(Store{{Singular}}Request $request): JsonResponse
    {
        $data = $request->validated();
        $idEmpresa = $this->resolveIdEmpresa(
            $request,
            isset($data['id_empresa']) ? (int) $data['id_empresa'] : null
        );
        $data['id_empresa'] = $idEmpresa;

        $dto = {{Singular}}DTO::fromRequest($data);
        $model = $this->createUseCase->execute($dto);

        return response()->json([
            'message' => '{{Singular}} criado com sucesso.',
            'data' => new {{Singular}}Resource($model),
        ], 201);
    }

    public function show(Request $request, string $id): {{Singular}}Resource
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);

        return new {{Singular}}Resource($model);
    }

    public function update(Update{{Singular}}Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->repository->findById($idEmpresa, $id);
        $dto = {{Singular}}DTO::fromRequest($request->validated());
        $updated = $this->updateUseCase->execute($model, $dto);

        return response()->json([
            'message' => '{{Singular}} atualizado com sucesso.',
            'data' => new {{Singular}}Resource($updated),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $this->deleteUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => '{{Singular}} removido com sucesso.',
        ]);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $idEmpresa = $this->resolveIdEmpresa($request);
        $model = $this->restoreUseCase->execute($idEmpresa, $id);

        return response()->json([
            'message' => '{{Singular}} restaurado com sucesso.',
            'data' => new {{Singular}}Resource($model),
        ]);
    }
}
PHP;

        File::put(
            "{$path}/Http/Controllers/{$ctx['singular']}Controller.php",
            $this->replacePlaceholders($content, $ctx)
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateRequests(string $path, array $ctx): void
    {
        $contentStore = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Store{{Singular}}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_empresa' => ['sometimes', 'integer', 'exists:empresas,id'],
            // Demais regras (colunas em português)
        ];
    }
}
PHP;

        $contentUpdate = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Update{{Singular}}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_empresa' => ['prohibited'],
            // Demais regras (colunas em português)
        ];
    }
}
PHP;

        File::put(
            "{$path}/Http/Requests/Store{$ctx['singular']}Request.php",
            $this->replacePlaceholders($contentStore, $ctx)
        );
        File::put(
            "{$path}/Http/Requests/Update{$ctx['singular']}Request.php",
            $this->replacePlaceholders($contentUpdate, $ctx)
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateResources(string $path, array $ctx): void
    {
        $content = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {{Singular}}Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
PHP;

        File::put(
            "{$path}/Http/Resources/{$ctx['singular']}Resource.php",
            $this->replacePlaceholders($content, $ctx)
        );
    }

    /**
     * @param  array{moduleName: string, singular: string, plural: string, table: string, routePrefix: string, variable: string}  $ctx
     */
    private function generateUseCases(string $path, array $ctx): void
    {
        $createContent = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\UseCases;

use App\Modules\{{Module}}\Contracts\{{Singular}}RepositoryInterface;
use App\Modules\{{Module}}\DTOs\{{Singular}}DTO;
use App\Modules\{{Module}}\Models\{{Singular}}Model;

class Create{{Singular}}UseCase
{
    public function __construct(
        private readonly {{Singular}}RepositoryInterface $repository
    ) {}

    public function execute({{Singular}}DTO $dto): {{Singular}}Model
    {
        return $this->repository->create($dto);
    }
}
PHP;

        $updateContent = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\UseCases;

use App\Modules\{{Module}}\Contracts\{{Singular}}RepositoryInterface;
use App\Modules\{{Module}}\DTOs\{{Singular}}DTO;
use App\Modules\{{Module}}\Models\{{Singular}}Model;

class Update{{Singular}}UseCase
{
    public function __construct(
        private readonly {{Singular}}RepositoryInterface $repository
    ) {}

    public function execute({{Singular}}Model $model, {{Singular}}DTO $dto): {{Singular}}Model
    {
        return $this->repository->update($model, $dto);
    }
}
PHP;

        $deleteContent = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\UseCases;

use App\Modules\{{Module}}\Contracts\{{Singular}}RepositoryInterface;

class Delete{{Singular}}UseCase
{
    public function __construct(
        private readonly {{Singular}}RepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): void
    {
        $model = $this->repository->findById($idEmpresa, $id);
        $this->repository->delete($model);
    }
}
PHP;

        $restoreContent = <<<'PHP'
<?php

namespace App\Modules\{{Module}}\UseCases;

use App\Modules\{{Module}}\Contracts\{{Singular}}RepositoryInterface;
use App\Modules\{{Module}}\Models\{{Singular}}Model;

class Restore{{Singular}}UseCase
{
    public function __construct(
        private readonly {{Singular}}RepositoryInterface $repository
    ) {}

    public function execute(int $idEmpresa, string|int $id): {{Singular}}Model
    {
        return $this->repository->restore($idEmpresa, $id);
    }
}
PHP;

        File::put("{$path}/UseCases/Create{$ctx['singular']}UseCase.php", $this->replacePlaceholders($createContent, $ctx));
        File::put("{$path}/UseCases/Update{$ctx['singular']}UseCase.php", $this->replacePlaceholders($updateContent, $ctx));
        File::put("{$path}/UseCases/Delete{$ctx['singular']}UseCase.php", $this->replacePlaceholders($deleteContent, $ctx));
        File::put("{$path}/UseCases/Restore{$ctx['singular']}UseCase.php", $this->replacePlaceholders($restoreContent, $ctx));
    }

    private function registerServiceProvider(string $moduleName, string $singular): void
    {
        $providersPath = base_path('bootstrap/providers.php');

        if (! File::exists($providersPath)) {
            return;
        }

        $content = File::get($providersPath);
        $newProvider = "    App\\Modules\\{$moduleName}\\Providers\\{$singular}ServiceProvider::class,";

        if (str_contains($content, $newProvider)) {
            return;
        }

        if (str_contains($content, '// Módulos')) {
            $content = str_replace(
                '// Módulos',
                "// Módulos\n{$newProvider}",
                $content
            );
        } else {
            $content = preg_replace(
                '/(\s*\];\s*$)/',
                "\n{$newProvider}\n$1",
                $content
            );
        }

        File::put($providersPath, $content);
        $this->line('ServiceProvider registrado automaticamente em bootstrap/providers.php!');
    }
}
