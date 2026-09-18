<?php

namespace App\Modules\Barbeiros\Providers;

use App\Modules\Barbeiros\Contracts\BarbeiroRepositoryInterface;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Barbeiros\Observers\BarbeiroObserver;
use App\Modules\Barbeiros\Repositories\BarbeiroRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BarbeiroServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            BarbeiroRepositoryInterface::class,
            BarbeiroRepository::class,
        );
    }

    public function boot(): void
    {
        BarbeiroModel::observe(BarbeiroObserver::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../routes.php');
    }
}