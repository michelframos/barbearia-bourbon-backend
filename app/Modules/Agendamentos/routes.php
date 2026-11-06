<?php

use App\Modules\Agendamentos\Http\Controllers\AgendamentoController;
use App\Modules\Trinks\Http\Controllers\TrinksAgendamentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'empresa'])->prefix('agendamentos')->group(function () {
    Route::post('/trinks/importar', [TrinksAgendamentoController::class, 'importar']);
    Route::post('/trinks/exportar', [TrinksAgendamentoController::class, 'exportar']);
    Route::get('/', [AgendamentoController::class, 'index']);
    Route::get('/grade', [AgendamentoController::class, 'grade']);
    Route::post('/', [AgendamentoController::class, 'store']);
    Route::get('/{id}', [AgendamentoController::class, 'show']);
    Route::put('/{id}', [AgendamentoController::class, 'update']);
    Route::post('/{id}/cancelar', [AgendamentoController::class, 'cancel']);
    Route::delete('/{id}', [AgendamentoController::class, 'destroy']);
    Route::post('/{id}/restore', [AgendamentoController::class, 'restore']);
});
