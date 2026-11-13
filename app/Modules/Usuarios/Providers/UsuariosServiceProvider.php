<?php

namespace App\Modules\Usuarios\Providers;

use App\Modules\Usuarios\Contracts\UsuarioRepositoryInterface;
use App\Modules\Usuarios\Repositories\UsuarioRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UsuariosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UsuarioRepositoryInterface::class,
            UsuarioRepository::class,
        );
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes.php');
    }
}
