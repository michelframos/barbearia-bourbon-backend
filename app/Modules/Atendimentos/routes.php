<?php

use App\Modules\Atendimentos\Http\Controllers\AtendimentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'empresa'])->prefix('atendimentos')->group(function () {
    Route::get('/', [AtendimentoController::class, 'index']);
    Route::get('/painel', [AtendimentoController::class, 'painel']);
    Route::get('/painel-tv', [AtendimentoController::class, 'painelTv']);
    Route::post('/check-in', [AtendimentoController::class, 'checkIn']);
    Route::get('/{id}', [AtendimentoController::class, 'show']);
    Route::post('/{id}/chamar', [AtendimentoController::class, 'chamar']);
    Route::post('/{id}/iniciar', [AtendimentoController::class, 'iniciar']);
    Route::post('/{id}/concluir', [AtendimentoController::class, 'concluir']);
    Route::post('/{id}/cancelar', [AtendimentoController::class, 'cancelar']);
});
