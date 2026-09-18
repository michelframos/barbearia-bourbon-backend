<?php

use App\Modules\Servicos\Http\Controllers\ServicoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'empresa'])->prefix('servicos')->group(function () {
    Route::get('/', [ServicoController::class, 'index']);
    Route::post('/', [ServicoController::class, 'store']);
    Route::get('/{id}', [ServicoController::class, 'show']);
    Route::put('/{id}', [ServicoController::class, 'update']);
    Route::delete('/{id}', [ServicoController::class, 'destroy']);
    Route::post('/{id}/restore', [ServicoController::class, 'restore']);
});