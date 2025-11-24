<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * MonitorStats 模型
 *
 * 监控任务统计数据
 * 聚合每个探针执行监控任务的成功/失败统计
 */
class MonitorStats extends Model
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
        'agent_id',
        'monitor_id',
        'monitor_type',
        'target',
        'current_response',
        'avg_response_24h',
        'uptime_24h',
        'uptime_30d',
        'cert_expiry_date',
        'cert_expiry_days',
        'total_checks_24h',
        'success_checks_24h',
        'total_checks_30d',
        'success_checks_30d',
        'last_check_time',
        'last_check_status',
        'updated_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'current_response' => 'integer',
        'avg_response_24h' => 'integer',
        'uptime_24h' => 'float',
        'uptime_30d' => 'float',
        'cert_expiry_date' => 'integer',
        'cert_expiry_days' => 'integer',
        'total_checks_24h' => 'integer',
        'success_checks_24h' => 'integer',
        'total_checks_30d' => 'integer',
        'success_checks_30d' => 'integer',
        'last_check_time' => 'integer',
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
            $model->updated_at = now()->timestamp * 1000;
        });

        static::updating(function ($model) {
            $model->updated_at = now()->timestamp * 1000;
        });
    }

    /**
     * 关联的探针
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * 关联的监控任务
     */
    public function monitorTask(): BelongsTo
    {
        return $this->belongsTo(MonitorTask::class, 'monitor_id');
    }

    /**
     * 计算 24 小时成功率
     *
     * @return float
     */
    public function getSuccessRate24h(): float
    {
        if ($this->total_checks_24h === 0) {
            return 0.0;
        }
        return round(($this->success_checks_24h / $this->total_checks_24h) * 100, 2);
    }

    /**
     * 计算 30 天成功率
     *
     * @return float
     */
    public function getSuccessRate30d(): float
    {
        if ($this->total_checks_30d === 0) {
            return 0.0;
        }
        return round(($this->success_checks_30d / $this->total_checks_30d) * 100, 2);
    }
}
