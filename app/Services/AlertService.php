<?php

namespace App\Services;

use App\Models\AlertConfig;
use App\Models\AlertRecord;
use App\Models\Agent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AlertService
{
    /**
     * 检查所有指标并触发告警
     *
     * @param string $agentId
     * @param float|null $cpuUsage
     * @param float|null $memoryUsage
     * @param float|null $diskUsage
     */
    public function checkMetrics(string $agentId, ?float $cpuUsage, ?float $memoryUsage, ?float $diskUsage): void
    {
        // 获取该 Agent 启用的告警配置
        // 暂时简化：只取第一条匹配的配置，或者后续支持多条
        $configs = AlertConfig::where('agent_id', $agentId)
            ->where('enabled', true)
            ->get();

        if ($configs->isEmpty()) {
            return;
        }

        $agent = Agent::find($agentId);
        if (!$agent) return;

        foreach ($configs as $config) {
            // 检查 CPU
            if ($config->rule_cpu_enabled && $cpuUsage !== null) {
                $this->checkRule($agent, $config, 'cpu', $cpuUsage, $config->rule_cpu_threshold, $config->rule_cpu_duration);
            }

            // 检查 内存
            if ($config->rule_memory_enabled && $memoryUsage !== null) {
                $this->checkRule($agent, $config, 'memory', $memoryUsage, $config->rule_memory_threshold, $config->rule_memory_duration);
            }

            // 检查 磁盘
            if ($config->rule_disk_enabled && $diskUsage !== null) {
                $this->checkRule($agent, $config, 'disk', $diskUsage, $config->rule_disk_threshold, $config->rule_disk_duration);
            }
        }
    }

    /**
     * 检查单个规则逻辑
     */
    protected function checkRule(Agent $agent, AlertConfig $config, string $type, float $currentValue, float $threshold, int $duration): void
    {

        $cacheKey = "alert_state:{$agent->id}:{$config->id}:{$type}";
        $state = Cache::get($cacheKey, [
            'start_time' => 0,
            'is_firing' => false,
            'last_record_id' => 0
        ]);

        $now = now()->timestamp * 1000; // 毫秒

        // === 1. 超过阈值 ===
        if ($currentValue >= $threshold) {
            // 如果是第一次超过，记录开始时间
            if ($state['start_time'] == 0) {
                $state['start_time'] = $now;
                Cache::put($cacheKey, $state, 3600); // 缓存 1 小时
                return;
            }

            // 检查持续时间 (毫秒 -> 秒)
            $elapsedSeconds = ($now - $state['start_time']) / 1000;

            if ($elapsedSeconds >= $duration) {
                // 达到持续时间，且当前未在报警 -> 触发告警
                if (!$state['is_firing']) {
                    $recordId = $this->fireAlert($agent, $config, $type, $currentValue, $threshold, $duration);

                    $state['is_firing'] = true;
                    $state['last_record_id'] = $recordId;
                    Cache::put($cacheKey, $state, 3600);
                }
            }
        }
        // === 2. 未超过阈值 (恢复) ===
        else {
            // 如果之前在报警 -> 触发恢复
            if ($state['is_firing']) {
                $this->resolveAlert($agent, $config, $type, $currentValue, $state['last_record_id']);

                $state['is_firing'] = false;
                $state['last_record_id'] = 0;
            }

            // 重置开始时间
            $state['start_time'] = 0;
            Cache::put($cacheKey, $state, 3600);
        }
    }

    /**
     * 触发告警动作
     */
    protected function fireAlert(Agent $agent, AlertConfig $config, string $type, float $value, float $threshold, int $duration): int
    {
        $message = $this->buildMessage($type, $value, $threshold, $duration);
        $typeName = $this->getTypeName($type);

        Log::warning("==================== 触发告警 ====================");
        Log::warning("[告警] 主机: {$agent->hostname} | 类型: {$typeName} | 当前: {$value}% | 阈值: {$threshold}%");
        Log::warning("==================================================");

        $record = AlertRecord::create([
            'agent_id' => $agent->id,
            'config_id' => $config->id,
            'config_name' => $config->name,
            'alert_type' => $type,
            'message' => $message,
            'threshold' => $threshold,
            'actual_value' => $value,
            'level' => 'warning', // 简单起见固定为 warning，也可以根据超标程度判断
            'status' => 'firing',
            'fired_at' => now()->timestamp * 1000,
            'created_at' => now()->timestamp * 1000,
            'updated_at' => now()->timestamp * 1000,
        ]);

        // TODO: 发送通知 (邮件/Webhook)

        return $record->id;
    }

    /**
     * 触发恢复动作
     */
    protected function resolveAlert(Agent $agent, AlertConfig $config, string $type, float $value, int $lastRecordId): void
    {
        $typeName = $this->getTypeName($type);
        Log::info("-------------------- 告警恢复 --------------------");
        Log::info("[恢复] 主机: {$agent->hostname} | 类型: {$typeName} | 当前: {$value}%");
        Log::info("--------------------------------------------------");

        // 更新历史记录
        if ($lastRecordId) {
            $record = AlertRecord::find($lastRecordId);
            if ($record) {
                $record->update([
                    'status' => 'resolved',
                    'resolved_at' => now()->timestamp * 1000,
                    'updated_at' => now()->timestamp * 1000,
                ]);
            }
        }

        // TODO: 发送恢复通知
    }

    protected function buildMessage(string $type, float $value, float $threshold, int $duration): string
    {
        $typeName = $this->getTypeName($type);
        return "{$typeName} 持续 {$duration} 秒超过 " . round($threshold, 1) . "%，当前值 " . round($value, 1) . "%";
    }

    protected function getTypeName(string $type): string
    {
        return match ($type) {
            'cpu' => 'CPU',
            'memory' => '内存',
            'disk' => '磁盘',
            default => $type,
        };
    }
}
