<?php

namespace App\Services;

use App\Models\MonitorTask;
use App\Models\MonitorMetric;
use App\Models\MonitorStats;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MonitorService
{
    /**
     * 创建监控任务
     */
    public function createTask(array $data): MonitorTask
    {
        return MonitorTask::create($data);
    }

    /**
     * 更新监控任务
     */
    public function updateTask(string $id, array $data): ?MonitorTask
    {
        $task = MonitorTask::find($id);
        if (!$task) {
            return null;
        }

        $task->update($data);
        return $task;
    }

    /**
     * 删除监控任务
     */
    public function deleteTask(string $id): bool
    {
        $task = MonitorTask::find($id);
        if (!$task) {
            return false;
        }

        $task->delete();
        return true;
    }

    /**
     * 获取监控任务详情
     */
    public function findTask(string $id): ?MonitorTask
    {
        return MonitorTask::find($id);
    }

    /**
     * 获取监控任务列表
     */
    public function listTasks(array $filters, int $pageSize = 10): LengthAwarePaginator
    {
        $query = MonitorTask::query();

        // 过滤条件
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['enabled'])) {
            $query->where('enabled', $filters['enabled']);
        }

        // 排序
        $query->orderBy('created_at', 'desc');

        return $query->paginate($pageSize);
    }

    /**
     * 获取探针的监控任务列表
     */
    public function getTasksForAgent(string $agentId): Collection
    {
        return MonitorTask::where('enabled', true)
            ->where(function ($query) use ($agentId) {
                $query->whereNull('agent_ids')
                    ->orWhereJsonContains('agent_ids', $agentId);
            })
            ->get();
    }

    /**
     * 存储监控检测结果
     */
    public function storeMetric(array $data): MonitorMetric
    {
        $metric = MonitorMetric::create($data);

        // 更新统计数据
        $this->updateStats($data['agent_id'], $data['monitor_id'], $data);

        return $metric;
    }

    /**
     * 批量存储监控检测结果
     */
    public function storeMetricsBatch(string $agentId, array $metrics): array
    {
        $count = 0;

        foreach ($metrics as $metric) {
            $metric['agent_id'] = $agentId;
            MonitorMetric::create($metric);
            $this->updateStats($agentId, $metric['monitor_id'], $metric);
            $count++;
        }

        return ['count' => $count];
    }

    /**
     * 获取监控历史数据
     */
    public function getMetricHistory(string $monitorId, string $range = '1h', ?string $agentId = null): array
    {
        $rangeMap = [
            '1h' => 3600,
            '6h' => 21600,
            '12h' => 43200,
            '24h' => 86400,
            '7d' => 604800,
        ];

        $seconds = $rangeMap[$range] ?? 3600;
        $startTime = (now()->timestamp - $seconds) * 1000;

        $query = MonitorMetric::where('monitor_id', $monitorId)
            ->where('timestamp', '>=', $startTime)
            ->orderBy('timestamp', 'asc');

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        $metrics = $query->get();

        return [
            'monitor_id' => $monitorId,
            'range' => $range,
            'data' => $metrics,
        ];
    }

    /**
     * 更新监控统计数据
     */
    protected function updateStats(string $agentId, string $monitorId, array $metricData): void
    {
        $stats = MonitorStats::where('agent_id', $agentId)
            ->where('monitor_id', $monitorId)
            ->first();

        if (!$stats) {
            $task = MonitorTask::find($monitorId);
            $stats = MonitorStats::create([
                'agent_id' => $agentId,
                'monitor_id' => $monitorId,
                'monitor_type' => $metricData['type'] ?? $task?->type ?? 'http',
                'target' => $metricData['target'] ?? $task?->target ?? '',
            ]);
        }

        // 更新最新状态
        $stats->current_response = $metricData['response_time'] ?? null;
        $stats->last_check_time = $metricData['timestamp'] ?? now()->timestamp * 1000;
        $stats->last_check_status = $metricData['status'] ?? 'unknown';

        // 更新 SSL 证书信息
        if (isset($metricData['cert_expiry_time'])) {
            $stats->cert_expiry_date = $metricData['cert_expiry_time'];
            $stats->cert_expiry_days = $metricData['cert_days_left'] ?? null;
        }

        $stats->save();

        // 重新计算统计数据
        $this->recalculateStats($stats);
    }

    /**
     * 重新计算统计数据
     */
    protected function recalculateStats(MonitorStats $stats): void
    {
        $now = now()->timestamp * 1000;
        $time24h = $now - 86400000;
        $time30d = $now - 2592000000;

        // 24 小时统计
        $metrics24h = MonitorMetric::where('agent_id', $stats->agent_id)
            ->where('monitor_id', $stats->monitor_id)
            ->where('timestamp', '>=', $time24h)
            ->get();

        if ($metrics24h->count() > 0) {
            $stats->total_checks_24h = $metrics24h->count();
            $stats->success_checks_24h = $metrics24h->where('status', 'up')->count();
            $stats->uptime_24h = round(($stats->success_checks_24h / $stats->total_checks_24h) * 100, 2);
            $stats->avg_response_24h = (int) $metrics24h->avg('response_time');
        }

        // 30 天统计
        $metrics30d = MonitorMetric::where('agent_id', $stats->agent_id)
            ->where('monitor_id', $stats->monitor_id)
            ->where('timestamp', '>=', $time30d)
            ->get();

        if ($metrics30d->count() > 0) {
            $stats->total_checks_30d = $metrics30d->count();
            $stats->success_checks_30d = $metrics30d->where('status', 'up')->count();
            $stats->uptime_30d = round(($stats->success_checks_30d / $stats->total_checks_30d) * 100, 2);
        }

        $stats->save();
    }

    /**
     * 获取监控任务的统计数据
     */
    public function getStats(string $monitorId, ?string $agentId = null): Collection
    {
        $query = MonitorStats::where('monitor_id', $monitorId);

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        return $query->get();
    }

    /**
     * 获取所有监控任务的汇总统计
     */
    public function getOverviewStats(): array
    {
        $totalTasks = MonitorTask::count();
        $enabledTasks = MonitorTask::where('enabled', true)->count();

        // 获取最近的检测统计
        $now = now()->timestamp * 1000;
        $time24h = $now - 86400000;

        $recentMetrics = MonitorMetric::where('timestamp', '>=', $time24h)->get();
        $totalChecks = $recentMetrics->count();
        $successChecks = $recentMetrics->where('status', 'up')->count();
        $avgResponseTime = (int) $recentMetrics->avg('response_time');

        $overallUptime = $totalChecks > 0 ? round(($successChecks / $totalChecks) * 100, 2) : 0;

        return [
            'total_tasks' => $totalTasks,
            'enabled_tasks' => $enabledTasks,
            'disabled_tasks' => $totalTasks - $enabledTasks,
            'total_checks_24h' => $totalChecks,
            'success_checks_24h' => $successChecks,
            'overall_uptime_24h' => $overallUptime,
            'avg_response_time_24h' => $avgResponseTime,
        ];
    }
}
