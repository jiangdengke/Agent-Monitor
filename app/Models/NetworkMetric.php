<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NetworkMetric 模型
 *
 * 网络流量指标
 * 存储网卡的收发数据量和数据包统计
 */
class NetworkMetric extends Model{
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
        'interface',
        'bytes_sent_rate',
        'bytes_recv_rate',
        'bytes_sent_total',
        'bytes_recv_total',
        'timestamp',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'bytes_sent_rate' => 'integer',
        'bytes_recv_rate' => 'integer',
        'bytes_sent_total' => 'integer',
        'bytes_recv_total' => 'integer',
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
}
