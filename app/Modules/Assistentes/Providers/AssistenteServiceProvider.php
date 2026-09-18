<?php

namespace App\Modules\Assistentes\Providers;

use App\Modules\Assistentes\Contracts\AssistenteRepositoryInterface;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Assistentes\Observers\AssistenteObserver;
use App\Modules\Assistentes\Repositories\AssistenteRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AssistenteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AssistenteRepositoryInterface::class,
            AssistenteRepository::class,
        );
    }

    public function boot(): void
    {
        AssistenteModel::observe(AssistenteObserver::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../routes.php');
    }
}