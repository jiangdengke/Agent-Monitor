<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResponseCodeEnum;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Jiannei\Response\Laravel\Support\Facades\Response;

class AgentController extends Controller
{
    /**
     * agent 注册
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

        $agent = Agent::where('hostname', $validated['hostname'])
            ->where('ip', $validated['ip'])
            ->first();

        if ($agent) {
            // 存在，更新信息
            $agent->update([
                'os' => $validated['os'] ?? $agent->os,
                'arch' => $validated['arch'] ?? $agent->arch,
                'version' => $validated['version'] ?? $agent->version,
                'status' => 1,
            ]);
            $agent->updateHeartbeat();
            return Response::success($agent, '', ResponseCodeEnum::AGENT_UPDATE_SUCCESS);
        }

        // 创建新探针
        $agent = Agent::create([
            'name' => $validated['hostname'],
            'hostname' => $validated['hostname'],
            'ip' => $validated['ip'],
            'os' => $validated['os'] ?? '',
            'arch' => $validated['arch'] ?? '',
            'version' => $validated['version'] ?? '',
            'status' => 1,
        ]);
        return Response::success($agent, '', ResponseCodeEnum::AGENT_REGISTER_SUCCESS);
    }

    /**
     * agent 心跳
     */
    public function heartbeat(Request $request, string $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) {
            return Response::fail('', ResponseCodeEnum::AGENT_NOT_FOUND);
        }
        $agent->updateHeartbeat();
        $agent->status = 1;
        $agent->save();
        return Response::success([
            'agent_id' => $agent->id,
            'last_seen_at' => $agent->last_seen_at,
        ], '', ResponseCodeEnum::AGENT_HEARTBEAT_SUCCESS);
    }

    /**
     * 获取探针列表
     */
    public function index(): JsonResponse
    {
        $agents = Agent::orderBy('last_seen_at', 'desc')->get();
        return Response::success($agents, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取每个探针信息
     */
    public function show(string $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) {
            return Response::fail('', ResponseCodeEnum::AGENT_NOT_FOUND);
        }
        return Response::success($agent, '', ResponseCodeEnum::SUCCESS);
    }
}
