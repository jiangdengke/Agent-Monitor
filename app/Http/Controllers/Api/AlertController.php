<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlertConfig;
use App\Models\AlertRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jiannei\Response\Laravel\Support\Facades\Response;

class AlertController extends Controller
{
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

        $config = AlertConfig::create($validated);

        return Response::success($config);
    }

    /**
     * 更新告警配置
     */
    public function updateConfig(Request $request, string $id): JsonResponse
    {
        $config = AlertConfig::find($id);
        if (!$config) {
            return Response::fail('告警配置不存在');
        }

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

        $config->update($validated);

        return Response::success($config);
    }

    /**
     * 删除告警配置
     */
    public function deleteConfig(string $id): JsonResponse
    {
        $config = AlertConfig::find($id);
        if (!$config) {
            return Response::fail('告警配置不存在');
        }

        $config->delete();

        return Response::success(null, '删除成功');
    }

    /**
     * 获取告警配置详情
     */
    public function getConfig(string $id): JsonResponse
    {
        $config = AlertConfig::find($id);
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
        $configs = AlertConfig::where('agent_id', $agentId)->get();

        return Response::success($configs);
    }

    /**
     * 列出告警记录
     */
    public function listRecords(Request $request): JsonResponse
    {
        $agentId = $request->query('agentId');
        $limit = $request->query('limit', 20);
        $offset = $request->query('offset', 0);

        $query = AlertRecord::query()->orderBy('fired_at', 'desc');

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        $total = $query->count();
        $records = $query->skip($offset)->take($limit)->get();

        return Response::success([
            'records' => $records,
            'total' => $total,
            'limit' => (int) $limit,
            'offset' => (int) $offset,
        ]);
    }
}
