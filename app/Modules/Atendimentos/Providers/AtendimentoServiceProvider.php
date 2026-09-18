<?php

namespace App\Modules\Atendimentos\Providers;

use App\Modules\Atendimentos\Contracts\AtendimentoRepositoryInterface;
use App\Modules\Atendimentos\Contracts\PainelTvSnapshotProxyInterface;
use App\Modules\Atendimentos\Contracts\StatusAtendimentoStrategyInterface;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Atendimentos\Observers\AtendimentoObserver;
use App\Modules\Atendimentos\Proxies\CachedPainelTvSnapshotProxy;
use App\Modules\Atendimentos\Repositories\AtendimentoRepository;
use App\Modules\Atendimentos\Services\AssistenteElegibilidadeService;
use App\Modules\Atendimentos\Services\AssistenteSelectionResolver;
use App\Modules\Atendimentos\Strategies\FluxoStatusAtendimentoStrategy;
use App\Modules\Atendimentos\Strategies\ManualAssistenteSelectionStrategy;
use App\Modules\Atendimentos\Strategies\SequencialAssistenteSelectionStrategy;
use App\Modules\Core\Services\EmpresaConfigService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AtendimentoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AtendimentoRepositoryInterface::class,
            AtendimentoRepository::class,
        );

        $this->app->bind(
            StatusAtendimentoStrategyInterface::class,
            FluxoStatusAtendimentoStrategy::class,
        );

        $this->app->bind(
            PainelTvSnapshotProxyInterface::class,
            CachedPainelTvSnapshotProxy::class,
        );

        $this->app->singleton(EmpresaConfigService::class);
        $this->app->singleton(AssistenteElegibilidadeService::class);
        $this->app->singleton(AssistenteSelectionResolver::class);
        $this->app->singleton(ManualAssistenteSelectionStrategy::class);
        $this->app->singleton(SequencialAssistenteSelectionStrategy::class);
    }

    public function boot(): void
    {
        AtendimentoModel::observe(AtendimentoObserver::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes.php');
    }
}
