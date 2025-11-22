<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MonitorStats 模型
 *
 * 监控任务统计数据
 * 聚合每个探针执行监控任务的成功/失败统计
 */
class MonitorStats extends Model
{
    // 禁用 Laravel 自动时间戳管理
    public $timestamps = false;

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'agent_id',
        'monitor_id',
        'total_checks',
        'successful_checks',
        'failed_checks',
        'avg_response_time',
        'last_check_at',
        'timestamp',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'total_checks' => 'integer',
        'successful_checks' => 'integer',
        'failed_checks' => 'integer',
        'avg_response_time' => 'integer',
        'last_check_at' => 'integer',
        'timestamp' => 'integer',
        'created_at' => 'integer',
    ];

    /**
     * 模型启动方法
     * 自动设置毫秒时间戳
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->timestamp * 1000;
            }
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
     * 计算成功率
     *
     * @return float
     */
    public function getSuccessRate(): float
    {
        if ($this->total_checks === 0) {
            return 0.0;
        }
        return round(($this->successful_checks / $this->total_checks) * 100, 2);
    }
}
