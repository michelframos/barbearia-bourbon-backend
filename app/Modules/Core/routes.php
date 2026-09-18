<?php

use App\Modules\Core\Http\Controllers\EmpresaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'empresa'])->group(function () {
    Route::get('/empresa', [EmpresaController::class, 'show']);
    Route::put('/empresa', [EmpresaController::class, 'update']);
});
