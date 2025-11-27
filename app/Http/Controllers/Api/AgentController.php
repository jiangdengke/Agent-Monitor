<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResponseCodeEnum;
use App\Http\Controllers\Controller;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jiannei\Response\Laravel\Support\Facades\Response;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentService $agentService
    ) {}

    /**
     * Agent 注册
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hostname' => 'required|string|max:255',
            'ip' => 'required|ip',
            'os' => 'nullable|string|max:100',
            'arch' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:50',
        ]);

        $result = $this->agentService->register($validated);

        if ($result['isNew']) {
            return Response::success($result['agent'], '', ResponseCodeEnum::AGENT_REGISTER_SUCCESS);
        }

        return Response::success($result['agent'], '', ResponseCodeEnum::AGENT_UPDATE_SUCCESS);
    }

    /**
     * Agent 心跳
     */
    public function heartbeat(string $id): JsonResponse
    {
        $agent = $this->agentService->heartbeat($id);

        if (!$agent) {
            return Response::fail('', ResponseCodeEnum::AGENT_NOT_FOUND);
        }

        return Response::success([
            'agent_id' => $agent->id,
            'last_seen_at' => $agent->last_seen_at,
        ], '', ResponseCodeEnum::AGENT_HEARTBEAT_SUCCESS);
    }

    /**
     * 获取探针列表
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['hostname', 'ip', 'status', 'sortField', 'sortOrder']);
        $pageSize = $request->input('pageSize', 10);

        $agents = $this->agentService->list($filters, $pageSize);

        return Response::success([
            'items' => $agents->items(),
            'total' => $agents->total(),
        ], '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取探针详情
     */
    public function show(string $id): JsonResponse
    {
        $agent = $this->agentService->find($id);

        if (!$agent) {
            return Response::fail('', ResponseCodeEnum::AGENT_NOT_FOUND);
        }

        return Response::success($agent, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 更新探针信息
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'expireTime' => 'nullable|integer',
        ]);

        $agent = $this->agentService->update($id, $validated);

        if (!$agent) {
            return Response::fail('', ResponseCodeEnum::AGENT_NOT_FOUND);
        }

        return Response::success($agent, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取探针统计数据
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->agentService->statistics();

        return Response::success($stats, '', ResponseCodeEnum::SUCCESS);
    }
}
