<?php

namespace App\Modules\Servicos\Providers;

use App\Modules\Servicos\Contracts\ServicoRepositoryInterface;
use App\Modules\Servicos\Models\ServicoModel;
use App\Modules\Servicos\Observers\ServicoObserver;
use App\Modules\Servicos\Repositories\ServicoRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ServicoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ServicoRepositoryInterface::class,
            ServicoRepository::class,
        );
    }

    public function boot(): void
    {
        ServicoModel::observe(ServicoObserver::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../routes.php');
    }
}