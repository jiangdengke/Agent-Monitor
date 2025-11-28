<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResponseCodeEnum;
use App\Http\Controllers\Controller;
use App\Services\MonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jiannei\Response\Laravel\Support\Facades\Response;

class MonitorController extends Controller
{
    public function __construct(
        private readonly MonitorService $monitorService
    ) {}

    /**
     * 创建监控任务
     */
    public function createTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:monitor_tasks,name',
            'type' => 'required|string|in:http,tcp,ping',
            'target' => 'required|string|max:500',
            'description' => 'nullable|string',
            'enabled' => 'boolean',
            'show_target_public' => 'boolean',
            'interval' => 'integer|min:10|max:3600',
            'agent_ids' => 'nullable|array',
            'agent_ids.*' => 'uuid|exists:agents,id',
            'http_config' => 'nullable|array',
            'tcp_config' => 'nullable|array',
        ]);

        $task = $this->monitorService->createTask($validated);

        return Response::success($task, '', ResponseCodeEnum::MONITOR_TASK_CREATE_SUCCESS);
    }

    /**
     * 更新监控任务
     */
    public function updateTask(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255|unique:monitor_tasks,name,' . $id,
            'type' => 'string|in:http,tcp,ping',
            'target' => 'string|max:500',
            'description' => 'nullable|string',
            'enabled' => 'boolean',
            'show_target_public' => 'boolean',
            'interval' => 'integer|min:10|max:3600',
            'agent_ids' => 'nullable|array',
            'agent_ids.*' => 'uuid|exists:agents,id',
            'http_config' => 'nullable|array',
            'tcp_config' => 'nullable|array',
        ]);

        $task = $this->monitorService->updateTask($id, $validated);

        if (!$task) {
            return Response::fail('', ResponseCodeEnum::MONITOR_TASK_NOT_FOUND);
        }

        return Response::success($task, '', ResponseCodeEnum::MONITOR_TASK_UPDATE_SUCCESS);
    }

    /**
     * 删除监控任务
     */
    public function deleteTask(string $id): JsonResponse
    {
        $deleted = $this->monitorService->deleteTask($id);

        if (!$deleted) {
            return Response::fail('', ResponseCodeEnum::MONITOR_TASK_NOT_FOUND);
        }

        return Response::success(null, '删除成功', ResponseCodeEnum::MONITOR_TASK_DELETE_SUCCESS);
    }

    /**
     * 获取监控任务详情
     */
    public function showTask(string $id): JsonResponse
    {
        $task = $this->monitorService->findTask($id);

        if (!$task) {
            return Response::fail('', ResponseCodeEnum::MONITOR_TASK_NOT_FOUND);
        }

        return Response::success($task, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取监控任务列表
     */
    public function listTasks(Request $request): JsonResponse
    {
        $filters = $request->only(['name', 'type', 'enabled']);
        $pageSize = $request->input('pageSize', 10);

        $tasks = $this->monitorService->listTasks($filters, $pageSize);

        return Response::success([
            'items' => $tasks->items(),
            'total' => $tasks->total(),
        ], '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取探针的监控任务列表
     */
    public function getTasksForAgent(string $agentId): JsonResponse
    {
        $tasks = $this->monitorService->getTasksForAgent($agentId);

        return Response::success($tasks, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 批量上报监控检测结果
     */
    public function storeMetrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'metrics' => 'required|array',
            'metrics.*.monitor_id' => 'required|string',
            'metrics.*.type' => 'required|string|in:http,tcp,ping',
            'metrics.*.target' => 'required|string',
            'metrics.*.status' => 'required|string|in:up,down',
            'metrics.*.status_code' => 'nullable|integer',
            'metrics.*.response_time' => 'nullable|integer',
            'metrics.*.error' => 'nullable|string',
            'metrics.*.message' => 'nullable|string',
            'metrics.*.content_match' => 'nullable|boolean',
            'metrics.*.cert_expiry_time' => 'nullable|integer',
            'metrics.*.cert_days_left' => 'nullable|integer',
            'metrics.*.timestamp' => 'required|integer',
        ]);

        $result = $this->monitorService->storeMetricsBatch(
            $validated['agent_id'],
            $validated['metrics']
        );

        return Response::success(['received' => $result['count']], '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取监控历史数据
     */
    public function getMetricHistory(Request $request, string $monitorId): JsonResponse
    {
        $validated = $request->validate([
            'range' => 'nullable|string|in:1h,6h,12h,24h,7d',
            'agent_id' => 'nullable|uuid',
        ]);

        $result = $this->monitorService->getMetricHistory(
            $monitorId,
            $validated['range'] ?? '1h',
            $validated['agent_id'] ?? null
        );

        return Response::success($result, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取监控任务的统计数据
     */
    public function getStats(Request $request, string $monitorId): JsonResponse
    {
        $agentId = $request->query('agent_id');

        $stats = $this->monitorService->getStats($monitorId, $agentId);

        return Response::success($stats, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取所有监控任务的汇总统计
     */
    public function getOverviewStats(): JsonResponse
    {
        $stats = $this->monitorService->getOverviewStats();

        return Response::success($stats, '', ResponseCodeEnum::SUCCESS);
    }
}
