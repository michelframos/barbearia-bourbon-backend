<?php

namespace App\Modules\Agendamentos\Providers;

use App\Modules\Agendamentos\Contracts\AgendamentoRepositoryInterface;
use App\Modules\Agendamentos\Contracts\DisponibilidadeSlotStrategyInterface;
use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Agendamentos\Observers\AgendamentoObserver;
use App\Modules\Agendamentos\Repositories\AgendamentoRepository;
use App\Modules\Agendamentos\Strategies\IntervaloEmpresaSlotStrategy;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AgendamentoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AgendamentoRepositoryInterface::class,
            AgendamentoRepository::class,
        );

        $this->app->bind(
            DisponibilidadeSlotStrategyInterface::class,
            IntervaloEmpresaSlotStrategy::class,
        );
    }

    public function boot(): void
    {
        AgendamentoModel::observe(AgendamentoObserver::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes.php');
    }
}
