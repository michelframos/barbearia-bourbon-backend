<?php

use App\Modules\Assistentes\Http\Controllers\AssistenteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'empresa'])->prefix('assistentes')->group(function () {
    Route::get('/', [AssistenteController::class, 'index']);
    Route::post('/', [AssistenteController::class, 'store']);
    Route::get('/{id}', [AssistenteController::class, 'show']);
    Route::put('/{id}', [AssistenteController::class, 'update']);
    Route::delete('/{id}', [AssistenteController::class, 'destroy']);
    Route::post('/{id}/restore', [AssistenteController::class, 'restore']);
});