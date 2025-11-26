<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MonitorMetric 模型
 *
 * 服务监控检测结果
 * 存储 HTTP/TCP 等服务监控的详细检测结果
 */
class MonitorMetric extends Model{
    // 使用自增主键
    protected $keyType = 'integer';
    public $incrementing = true;

    // 禁用 Laravel 自动时间戳管理
    public $timestamps = false;

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'agent_id',
        'monitor_id',
        'type',
        'target',
        'status',
        'status_code',
        'response_time',
        'error',
        'message',
        'content_match',
        'cert_expiry_time',
        'cert_days_left',
        'timestamp',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'status_code' => 'integer',
        'response_time' => 'integer',
        'content_match' => 'boolean',
        'cert_expiry_time' => 'integer',
        'cert_days_left' => 'integer',
        'timestamp' => 'integer',
    ];

    /**
     * 模型启动方法
     * 自动设置时间戳
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
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
}
