<?php

namespace App\Modules\Trinks\Providers;

use App\Modules\Trinks\Contracts\VerifiesTrinksSnsSignature;
use App\Modules\Trinks\Services\TrinksSnsSignatureVerifier;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TrinksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VerifiesTrinksSnsSignature::class, TrinksSnsSignatureVerifier::class);
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes.php');
    }
}
