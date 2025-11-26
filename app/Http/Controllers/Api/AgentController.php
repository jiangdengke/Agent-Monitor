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
     * 获取探针列表 (分页 & 搜索)
     *
     * 支持以下参数：
     * - hostname: 按主机名模糊搜索
     * - ip: 按IP模糊搜索
     * - status: 按状态筛选 (0=离线, 1=在线)
     * - sortField: 排序字段 (默认 last_seen_at)
     * - sortOrder: 排序方向 (asc/desc, 默认 desc)
     * - pageSize: 每页数量 (默认 10)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Agent::query();

        // 搜索条件
        if ($request->has('hostname')) {
            $query->where('hostname', 'like', '%' . $request->input('hostname') . '%');
        }
        if ($request->has('ip')) {
            $query->where('ip', 'like', '%' . $request->input('ip') . '%');
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // 排序
        $sortField = $request->input('sortField', 'last_seen_at');
        $sortOrder = $request->input('sortOrder', 'desc');
        // 防止 SQL 注入，限制排序字段
        if (in_array($sortField, ['name', 'hostname', 'ip', 'status', 'last_seen_at', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('last_seen_at', 'desc');
        }

        // 分页
        $pageSize = $request->input('pageSize', 10);
        $agents = $query->paginate($pageSize);

        return Response::success([
            'items' => $agents->items(),
            'total' => $agents->total(),
        ], '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 更新探针信息
     *
     * 允许管理员修改探针的元数据
     *
     * @param Request $request
     * @param string $id 探针ID
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) {
            return Response::fail('', ResponseCodeEnum::AGENT_NOT_FOUND);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'expireTime' => 'nullable|integer',
        ]);

        // 映射 expireTime 到数据库字段 expire_time
        $data = [];
        if (isset($validated['name'])) $data['name'] = $validated['name'];
        if (isset($validated['platform'])) $data['platform'] = $validated['platform'];
        if (isset($validated['location'])) $data['location'] = $validated['location'];
        if (isset($validated['expireTime'])) $data['expire_time'] = $validated['expireTime'];

        $agent->update($data);

        return Response::success($agent, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取探针统计数据
     *
     * 返回总数、在线数、离线数和在线率
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $total = Agent::count();
        $online = Agent::where('status', 1)->count();
        $offline = Agent::where('status', 0)->count();
        
        $onlineRate = $total > 0 ? round(($online / $total) * 100, 2) : 0;

        return Response::success([
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'onlineRate' => $onlineRate,
        ], '', ResponseCodeEnum::SUCCESS);
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
