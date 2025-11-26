<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * Agent 模型
 *
 * 探针（监控客户端）信息管理
 * 存储探针的基本信息和运行状态
 */

class Agent extends Model
{
    // 使用字符串类型主键（UUID）
    protected $keyType = 'string';
    public $incrementing = false;

    // 禁用 Laravel 自动时间戳管理，使用自定义毫秒时间戳
    public $timestamps = false;

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'id',
        'name',
        'hostname',
        'ip',
        'os',
        'arch',
        'version',
        'platform',
        'location',
        'expire_time',
        'status',
        'last_seen_at',
        'created_at',
        'updated_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'status' => 'integer',
        'expire_time' => 'integer',
        'last_seen_at' => 'integer',
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
            $model->last_seen_at = $now;
        });

        static::updating(function ($model) {
            $model->updated_at = now()->timestamp * 1000;
        });
    }

    // ==================== 关系定义 ====================

    /**
     * CPU 指标数据
     */
    public function cpuMetrics(): HasMany
    {
        return $this->hasMany(CpuMetric::class, 'agent_id');
    }

    /**
     * 内存指标数据
     */
    public function memoryMetrics(): HasMany
    {
        return $this->hasMany(MemoryMetric::class, 'agent_id');
    }

    /**
     * 磁盘指标数据
     */
    public function diskMetrics(): HasMany
    {
        return $this->hasMany(DiskMetric::class, 'agent_id');
    }

    /**
     * 磁盘 I/O 指标数据
     */
    public function diskIoMetrics(): HasMany
    {
        return $this->hasMany(DiskIoMetric::class, 'agent_id');
    }

    /**
     * 网络指标数据
     */
    public function networkMetrics(): HasMany
    {
        return $this->hasMany(NetworkMetric::class, 'agent_id');
    }

    /**
     * 负载指标数据
     */
    public function loadMetrics(): HasMany
    {
        return $this->hasMany(LoadMetric::class, 'agent_id');
    }

    /**
     * GPU 指标数据
     */
    public function gpuMetrics(): HasMany
    {
        return $this->hasMany(GpuMetric::class, 'agent_id');
    }

    /**
     * 温度指标数据
     */
    public function temperatureMetrics(): HasMany
    {
        return $this->hasMany(TemperatureMetric::class, 'agent_id');
    }

    /**
     * 主机系统信息
     */
    public function hostMetrics(): HasMany
    {
        return $this->hasMany(HostMetric::class, 'agent_id');
    }

    /**
     * 服务监控检测结果
     */
    public function monitorMetrics(): HasMany
    {
        return $this->hasMany(MonitorMetric::class, 'agent_id');
    }

    /**
     * 监控任务统计数据
     */
    public function monitorStats(): HasMany
    {
        return $this->hasMany(MonitorStats::class, 'agent_id');
    }

    /**
     * 告警配置
     */
    public function alertConfigs(): HasMany
    {
        return $this->hasMany(AlertConfig::class, 'agent_id');
    }

    /**
     * 告警记录
     */
    public function alertRecords(): HasMany
    {
        return $this->hasMany(AlertRecord::class, 'agent_id');
    }

    /**
     * 审计结果
     */
    public function auditResults(): HasMany
    {
        return $this->hasMany(AuditResult::class, 'agent_id');
    }

    // ==================== 辅助方法 ====================

    /**
     * 检查探针是否在线
     * 判断标准：最后心跳时间在 2 分钟内
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        $twoMinutesAgo = (now()->timestamp - 120) * 1000;
        return $this->last_seen_at >= $twoMinutesAgo;
    }

    /**
     * 更新探针心跳时间
     */
    public function updateHeartbeat(): void
    {
        $this->last_seen_at = now()->timestamp * 1000;
        $this->save();
    }

    /**
     * 获取探针状态文本
     *
     * @return string
     */
    public function getStatusText(): string
    {
        return match($this->status) {
            0 => '离线',
            1 => '在线',
            2 => '维护中',
            default => '未知',
        };
    }
}
