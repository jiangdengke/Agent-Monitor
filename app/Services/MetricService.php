<?php

namespace App\Services;

use App\Jobs\CheckAlerts;
use App\Models\Agent;
use App\Models\CpuMetric;
use App\Models\DiskIoMetric;
use App\Models\DiskMetric;
use App\Models\GpuMetric;
use App\Models\HostMetric;
use App\Models\LoadMetric;
use App\Models\MemoryMetric;
use App\Models\MonitorMetric;
use App\Models\NetworkMetric;
use App\Models\TemperatureMetric;
use Illuminate\Support\Facades\Log;

class MetricService
{
    /**
     * 指标类型与模型的映射
     */
    private const TYPE_MODEL_MAP = [
        'cpu' => CpuMetric::class,
        'memory' => MemoryMetric::class,
        'disk' => DiskMetric::class,
        'disk_io' => DiskIoMetric::class,
        'network' => NetworkMetric::class,
        'load' => LoadMetric::class,
        'gpu' => GpuMetric::class,
        'temperature' => TemperatureMetric::class,
        'host' => HostMetric::class,
        'monitor' => MonitorMetric::class,
    ];

    /**
     * 批量存储指标
     *
     * @param string $agentId
     * @param array $metrics
     * @return array ['count' => int, 'latestMetrics' => array]
     */
    public function storeBatch(string $agentId, array $metrics): array
    {
        $count = 0;
        $latestMetrics = [];

        foreach ($metrics as $metricData) {
            if (!isset($metricData['type']) || !isset($metricData['data'])) {
                continue;
            }

            $type = $metricData['type'];
            $data = $metricData['data'];
            $data['agent_id'] = $agentId;

            if (!isset($data['timestamp'])) {
                $data['timestamp'] = $metricData['timestamp'] ?? (now()->timestamp * 1000);
            }

            // 收集关键指标用于告警检查
            $this->collectAlertMetrics($type, $data, $latestMetrics);

            // 存储指标
            if ($this->storeMetric($type, $data)) {
                $count++;
            }
        }

        // 记录日志
        $this->logMetrics($agentId, $latestMetrics);

        // 触发告警检查
        $this->triggerAlertCheck($agentId, $latestMetrics);

        return [
            'count' => $count,
            'latestMetrics' => $latestMetrics,
        ];
    }

    /**
     * 存储单个指标
     */
    private function storeMetric(string $type, array $data): bool
    {
        $modelClass = self::TYPE_MODEL_MAP[$type] ?? null;

        if (!$modelClass) {
            return false;
        }

        try {
            $modelClass::create($data);
            return true;
        } catch (\Exception $e) {
            Log::error("[存储失败] {$type}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 收集用于告警的关键指标
     */
    private function collectAlertMetrics(string $type, array $data, array &$latestMetrics): void
    {
        match ($type) {
            'cpu' => $latestMetrics['cpu'] = $data['usage_percent'] ?? null,
            'memory' => $latestMetrics['memory'] = $data['usage_percent'] ?? null,
            'disk' => $latestMetrics['disk'] = $data['usage_percent'] ?? null,
            default => null,
        };
    }

    /**
     * 记录指标日志
     */
    private function logMetrics(string $agentId, array $latestMetrics): void
    {
        $agent = Agent::find($agentId);
        $hostname = $agent->hostname ?? $agentId;
        $cpu = round($latestMetrics['cpu'] ?? 0, 1);
        $mem = round($latestMetrics['memory'] ?? 0, 1);
        $disk = round($latestMetrics['disk'] ?? 0, 1);

        Log::info("[指标] {$hostname} | CPU:{$cpu}% | 内存:{$mem}% | 磁盘:{$disk}%");
    }

    /**
     * 触发告警检查
     */
    private function triggerAlertCheck(string $agentId, array $latestMetrics): void
    {
        if (!empty($latestMetrics)) {
            CheckAlerts::dispatch($agentId, $latestMetrics);
        }
    }

    /**
     * 查询指标历史数据
     *
     * @param string $agentId
     * @param string $type
     * @param string $range
     * @return array|null
     */
    public function getHistory(string $agentId, string $type, string $range = '1h'): ?array
    {
        $modelClass = self::TYPE_MODEL_MAP[$type] ?? null;

        if (!$modelClass) {
            return null;
        }

        $startTime = $this->calculateStartTime($range);

        $data = $modelClass::where('agent_id', $agentId)
            ->where('timestamp', '>=', $startTime)
            ->orderBy('timestamp', 'asc')
            ->get();

        return [
            'agentId' => $agentId,
            'type' => $type,
            'range' => $range,
            'metrics' => $data,
        ];
    }

    /**
     * 计算查询起始时间
     */
    private function calculateStartTime(string $range): int
    {
        $now = now();

        return match ($range) {
            '6h' => $now->subHours(6)->timestamp * 1000,
            '12h' => $now->subHours(12)->timestamp * 1000,
            '24h' => $now->subDay()->timestamp * 1000,
            '7d' => $now->subDays(7)->timestamp * 1000,
            default => $now->subHour()->timestamp * 1000,
        };
    }

    /**
     * 获取支持的指标类型
     */
    public static function getSupportedTypes(): array
    {
        return array_keys(self::TYPE_MODEL_MAP);
    }

    /**
     * 获取 Agent 的最新指标
     */
    public function getLatest(string $agentId, ?string $type = null): array
    {
        $types = $type ? [$type] : ['cpu', 'memory', 'disk', 'disk_io', 'network', 'load', 'gpu', 'temperature'];
        $result = [];

        foreach ($types as $t) {
            $modelClass = self::TYPE_MODEL_MAP[$t] ?? null;
            if (!$modelClass) {
                continue;
            }

            $latest = $modelClass::where('agent_id', $agentId)
                ->orderBy('timestamp', 'desc')
                ->first();

            if ($latest) {
                $result[$t] = $latest;
            }
        }

        return $result;
    }
}
