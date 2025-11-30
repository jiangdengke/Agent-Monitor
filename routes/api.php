<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\MetricController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\MonitorController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\AuthController;

// 认证路由
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// 公共 API（无需认证）
Route::prefix('public')->group(function () {
    // 探针公共接口
    Route::get('/agents', [AgentController::class, 'index']);
    Route::get('/agents/statistics', [AgentController::class, 'statistics']);
    Route::get('/agents/{id}', [AgentController::class, 'show']);
    Route::get('/agents/{agent_id}/metrics/latest', [MetricController::class, 'latest']);

    // 监控公共接口
    Route::get('/monitors', [MonitorController::class, 'listTasks']);
    Route::get('/monitors/overview', [MonitorController::class, 'getOverviewStats']);
});

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
        // 最新指标
        Route::get('/{agent_id}/metrics/latest', [MetricController::class, 'latest']);

        // 探针告警配置
        Route::get('/{agentId}/alert-configs', [AlertController::class, 'listConfigsByAgent']);
    });

    // 告警路由
    Route::prefix('alert-configs')->group(function () {
        Route::post('/', [AlertController::class, 'createConfig']);
        Route::get('/{id}', [AlertController::class, 'getConfig']);
        Route::put('/{id}', [AlertController::class, 'updateConfig']);
        Route::delete('/{id}', [AlertController::class, 'deleteConfig']);
    });

    // 告警记录
    Route::get('/alert-records', [AlertController::class, 'listRecords']);

    // 监控任务路由
    Route::prefix('monitors')->group(function () {
        Route::get('/overview', [MonitorController::class, 'getOverviewStats']);
        Route::get('/', [MonitorController::class, 'listTasks']);
        Route::post('/', [MonitorController::class, 'createTask']);
        Route::get('/{id}', [MonitorController::class, 'showTask']);
        Route::put('/{id}', [MonitorController::class, 'updateTask']);
        Route::delete('/{id}', [MonitorController::class, 'deleteTask']);
        Route::get('/{id}/stats', [MonitorController::class, 'getStats']);
        Route::get('/{id}/history', [MonitorController::class, 'getMetricHistory']);
    });

    // 监控检测结果上报
    Route::post('/monitor-metrics', [MonitorController::class, 'storeMetrics']);

    // 探针获取监控任务
    Route::get('/agents/{agentId}/monitors', [MonitorController::class, 'getTasksForAgent']);

    // API Key 管理路由
    Route::prefix('api-keys')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index']);
        Route::post('/', [ApiKeyController::class, 'create']);
        Route::get('/{id}', [ApiKeyController::class, 'show']);
        Route::put('/{id}', [ApiKeyController::class, 'update']);
        Route::delete('/{id}', [ApiKeyController::class, 'delete']);
        Route::post('/{id}/enable', [ApiKeyController::class, 'enable']);
        Route::post('/{id}/disable', [ApiKeyController::class, 'disable']);
        Route::post('/{id}/regenerate', [ApiKeyController::class, 'regenerate']);
    });

    // 属性配置路由
    Route::prefix('properties')->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::post('/', [PropertyController::class, 'store']);
        Route::post('/batch', [PropertyController::class, 'batchStore']);
        Route::get('/{id}', [PropertyController::class, 'show']);
        Route::delete('/{id}', [PropertyController::class, 'destroy']);
    });
});