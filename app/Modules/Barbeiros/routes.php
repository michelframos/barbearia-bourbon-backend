<?php

use App\Modules\Barbeiros\Http\Controllers\BarbeiroController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'empresa'])->prefix('barbeiros')->group(function () {
    Route::get('/', [BarbeiroController::class, 'index']);
    Route::post('/', [BarbeiroController::class, 'store']);
    Route::get('/{id}', [BarbeiroController::class, 'show']);
    Route::put('/{id}', [BarbeiroController::class, 'update']);
    Route::post('/{id}/foto', [BarbeiroController::class, 'uploadFoto']);
    Route::delete('/{id}/foto', [BarbeiroController::class, 'destroyFoto']);
    Route::delete('/{id}', [BarbeiroController::class, 'destroy']);
    Route::post('/{id}/restore', [BarbeiroController::class, 'restore']);
});