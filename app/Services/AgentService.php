<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class AgentService
{
    public function __construct(
        private readonly MetricService $metricService
    ) {}
    /**
     * 允许排序的字段
     */
    private const SORTABLE_FIELDS = ['name', 'hostname', 'ip', 'status', 'last_seen_at', 'created_at'];

    /**
     * 注册或更新探针
     *
     * @param array $data
     * @return array ['agent' => Agent, 'isNew' => bool]
     */
    public function register(array $data): array
    {
        $agent = Agent::where('hostname', $data['hostname'])
            ->where('ip', $data['ip'])
            ->first();

        if ($agent) {
            $agent->update([
                'os' => $data['os'] ?? $agent->os,
                'arch' => $data['arch'] ?? $agent->arch,
                'version' => $data['version'] ?? $agent->version,
                'status' => 1,
            ]);
            $agent->updateHeartbeat();

            Log::info("探针已更新: {$agent->hostname} ({$agent->ip})");

            return ['agent' => $agent, 'isNew' => false];
        }

        $agent = Agent::create([
            'name' => $data['hostname'],
            'hostname' => $data['hostname'],
            'ip' => $data['ip'],
            'os' => $data['os'] ?? '',
            'arch' => $data['arch'] ?? '',
            'version' => $data['version'] ?? '',
            'status' => 1,
        ]);

        Log::info("新探针已注册: {$agent->hostname} ({$agent->ip})");

        return ['agent' => $agent, 'isNew' => true];
    }

    /**
     * 处理心跳
     *
     * @param string $id
     * @return Agent|null
     */
    public function heartbeat(string $id): ?Agent
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return null;
        }

        $agent->updateHeartbeat();
        $agent->status = 1;
        $agent->save();

        Log::info("收到探针心跳: {$agent->hostname} ({$agent->id})");

        return $agent;
    }

    /**
     * 获取探针列表（带最新指标）
     *
     * @param array $filters
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function list(array $filters, int $pageSize = 10): LengthAwarePaginator
    {
        $query = Agent::query();

        // 搜索条件
        if (!empty($filters['hostname'])) {
            $query->where('hostname', 'like', '%' . $filters['hostname'] . '%');
        }
        if (!empty($filters['ip'])) {
            $query->where('ip', 'like', '%' . $filters['ip'] . '%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 排序
        $sortField = $filters['sortField'] ?? 'last_seen_at';
        $sortOrder = ($filters['sortOrder'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortField, self::SORTABLE_FIELDS)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('last_seen_at', 'desc');
        }

        $paginator = $query->paginate($pageSize);

        // 为每个 Agent 附加最新指标
        $paginator->getCollection()->transform(function ($agent) {
            $metrics = $this->metricService->getLatest($agent->id);

            $agent->cpu_usage = $metrics['cpu']->usage_percent ?? 0;
            $agent->memory_usage = $metrics['memory']->usage_percent ?? 0;
            $agent->disk_usage = $metrics['disk']->usage_percent ?? 0;
            $agent->network_tx_rate = $metrics['network']->bytes_sent_rate ?? 0;
            $agent->network_rx_rate = $metrics['network']->bytes_recv_rate ?? 0;
            $agent->network_tx_total = $metrics['network']->bytes_sent ?? 0;
            $agent->network_rx_total = $metrics['network']->bytes_recv ?? 0;

            return $agent;
        });

        return $paginator;
    }

    /**
     * 获取探针详情
     *
     * @param string $id
     * @return Agent|null
     */
    public function find(string $id): ?Agent
    {
        return Agent::find($id);
    }

    /**
     * 更新探针信息
     *
     * @param string $id
     * @param array $data
     * @return Agent|null
     */
    public function update(string $id, array $data): ?Agent
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return null;
        }

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['platform'])) $updateData['platform'] = $data['platform'];
        if (isset($data['location'])) $updateData['location'] = $data['location'];
        if (isset($data['expireTime'])) $updateData['expire_time'] = $data['expireTime'];

        $agent->update($updateData);

        return $agent;
    }

    /**
     * 获取统计数据
     *
     * @return array
     */
    public function statistics(): array
    {
        $total = Agent::count();
        $online = Agent::where('status', 1)->count();
        $offline = Agent::where('status', 0)->count();
        $onlineRate = $total > 0 ? round(($online / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'onlineRate' => $onlineRate,
        ];
    }
}
