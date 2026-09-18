<?php

use App\Modules\Dashboard\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'empresa'])->prefix('dashboard')->group(function () {
    Route::get('/hoje', [DashboardController::class, 'hoje']);
});
