<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\MetricController;

Route::middleware('auth.apikey')->group(function () {
    // Agent路由
    Route::prefix('agents')->group(function () {
        Route::post('/register', [AgentController::class, 'register']);
        Route::post('/{id}/heartbeat', [AgentController::class, 'heartbeat']);
        
        Route::get('/statistics', [AgentController::class, 'statistics']);
        Route::get('/', [AgentController::class, 'index']);
        Route::get('/{id}', [AgentController::class, 'show']);
        Route::put('/{id}', [AgentController::class, 'update']);
        
        // 指标批量上报路由
        Route::post('/metrics', [MetricController::class, 'store']);
        // 指标查询路由
        Route::get('/{agent_id}/metrics', [MetricController::class, 'index']);
    });
});