<?php

namespace App\Modules\Core\Providers;

use App\Modules\Core\Contracts\NotificadorInterface;
use App\Modules\Core\Adapters\LogNotificadorAdapter;
use App\Modules\Core\Http\Middleware\EnsureEmpresaContext;
use App\Modules\Core\Services\AuditLogService;
use App\Modules\Core\Services\EmpresaConfigService;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogService::class);
        $this->app->bind(NotificadorInterface::class, LogNotificadorAdapter::class);
    }

    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('empresa', EnsureEmpresaContext::class);

        $this->app->singleton(EmpresaConfigService::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes.php');
    }
}
