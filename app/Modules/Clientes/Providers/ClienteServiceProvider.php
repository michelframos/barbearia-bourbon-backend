<?php

namespace App\Modules\Clientes\Providers;

use App\Modules\Clientes\Contracts\ClienteRepositoryInterface;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Clientes\Observers\ClienteObserver;
use App\Modules\Clientes\Repositories\ClienteRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ClienteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ClienteRepositoryInterface::class,
            ClienteRepository::class,
        );
    }

    public function boot(): void
    {
        ClienteModel::observe(ClienteObserver::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../routes.php');
    }
}