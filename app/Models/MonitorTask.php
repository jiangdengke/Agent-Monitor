<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * MonitorTask 模型
 *
 * 监控任务配置
 * 定义服务监控任务的目标、频率和验证规则
 */
class MonitorTask extends Model
{
    // 使用字符串类型主键（UUID）
    protected $keyType = 'string';
    public $incrementing = false;

    // 禁用 Laravel 自动时间戳管理
    public $timestamps = false;

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'id',
        'name',
        'type',
        'target',
        'interval',
        'timeout',
        'agent_ids',
        'expect_code',
        'expect_content',
        'created_at',
        'updated_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'interval' => 'integer',
        'timeout' => 'integer',
        'agent_ids' => 'array', // JSON 数组
        'expect_code' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    /**
     * 模型启动方法
     * 自动生成 UUID 和毫秒时间戳
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            $now = now()->timestamp * 1000;
            $model->created_at = $now;
            $model->updated_at = $now;
        });

        static::updating(function ($model) {
            $model->updated_at = now()->timestamp * 1000;
        });
    }

    /**
     * 监控检测结果
     */
    public function monitorMetrics(): HasMany
    {
        return $this->hasMany(MonitorMetric::class, 'monitor_id');
    }

    /**
     * 监控统计数据
     */
    public function monitorStats(): HasMany
    {
        return $this->hasMany(MonitorStats::class, 'monitor_id');
    }

    /**
     * 检查探针是否被分配此监控任务
     *
     * @param string $agentId
     * @return bool
     */
    public function hasAgent(string $agentId): bool
    {
        return in_array($agentId, $this->agent_ids ?? []);
    }

    /**
     * 添加探针到监控任务
     *
     * @param string $agentId
     */
    public function addAgent(string $agentId): void
    {
        $agents = $this->agent_ids ?? [];
        if (!in_array($agentId, $agents)) {
            $agents[] = $agentId;
            $this->agent_ids = $agents;
            $this->save();
        }
    }

    /**
     * 从监控任务移除探针
     *
     * @param string $agentId
     */
    public function removeAgent(string $agentId): void
    {
        $agents = $this->agent_ids ?? [];
        $agents = array_values(array_filter($agents, fn($id) => $id !== $agentId));
        $this->agent_ids = $agents;
        $this->save();
    }
}
