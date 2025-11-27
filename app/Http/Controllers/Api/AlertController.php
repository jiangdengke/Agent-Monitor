<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jiannei\Response\Laravel\Support\Facades\Response;

class AlertController extends Controller
{
    public function __construct(
        private readonly AlertService $alertService
    ) {}

    /**
     * 创建告警配置
     */
    public function createConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'name' => 'required|string|max:255',
            'enabled' => 'boolean',
            'rule_cpu_enabled' => 'boolean',
            'rule_cpu_threshold' => 'nullable|numeric|min:0|max:100',
            'rule_cpu_duration' => 'nullable|integer|min:1',
            'rule_memory_enabled' => 'boolean',
            'rule_memory_threshold' => 'nullable|numeric|min:0|max:100',
            'rule_memory_duration' => 'nullable|integer|min:1',
            'rule_disk_enabled' => 'boolean',
            'rule_disk_threshold' => 'nullable|numeric|min:0|max:100',
            'rule_disk_duration' => 'nullable|integer|min:1',
        ]);

        $config = $this->alertService->createConfig($validated);

        return Response::success($config);
    }

    /**
     * 更新告警配置
     */
    public function updateConfig(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'enabled' => 'boolean',
            'rule_cpu_enabled' => 'boolean',
            'rule_cpu_threshold' => 'nullable|numeric|min:0|max:100',
            'rule_cpu_duration' => 'nullable|integer|min:1',
            'rule_memory_enabled' => 'boolean',
            'rule_memory_threshold' => 'nullable|numeric|min:0|max:100',
            'rule_memory_duration' => 'nullable|integer|min:1',
            'rule_disk_enabled' => 'boolean',
            'rule_disk_threshold' => 'nullable|numeric|min:0|max:100',
            'rule_disk_duration' => 'nullable|integer|min:1',
        ]);

        $config = $this->alertService->updateConfig($id, $validated);

        if (!$config) {
            return Response::fail('告警配置不存在');
        }

        return Response::success($config);
    }

    /**
     * 删除告警配置
     */
    public function deleteConfig(string $id): JsonResponse
    {
        $deleted = $this->alertService->deleteConfig($id);

        if (!$deleted) {
            return Response::fail('告警配置不存在');
        }

        return Response::success(null, '删除成功');
    }

    /**
     * 获取告警配置详情
     */
    public function getConfig(string $id): JsonResponse
    {
        $config = $this->alertService->findConfig($id);

        if (!$config) {
            return Response::fail('告警配置不存在');
        }

        return Response::success($config);
    }

    /**
     * 列出探针的告警配置
     */
    public function listConfigsByAgent(string $agentId): JsonResponse
    {
        $configs = $this->alertService->listConfigsByAgent($agentId);

        return Response::success($configs);
    }

    /**
     * 列出告警记录
     */
    public function listRecords(Request $request): JsonResponse
    {
        $agentId = $request->query('agentId');
        $limit = (int) $request->query('limit', 20);
        $offset = (int) $request->query('offset', 0);

        $result = $this->alertService->listRecords($agentId, $limit, $offset);

        return Response::success($result);
    }
}
