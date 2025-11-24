<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentController;
Route::middleware('auth.apikey')->group(function () {
    // Agent路由
    Route::prefix('agents')->group(function () {
        Route::post('/register', [AgentController::class, 'register']);
        Route::post('/{id}/heartbeat', [AgentController::class, 'heartbeat']);
        Route::get('/', [AgentController::class, 'index']);
        Route::get('/{id}', [AgentController::class, 'show']);
    });
});






