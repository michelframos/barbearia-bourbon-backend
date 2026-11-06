<?php

use App\Modules\Trinks\Http\Controllers\TrinksWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/trinks', TrinksWebhookController::class);
