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
class NetworkMetric extends Model
{
    // 禁用 Laravel 自动时间戳管理
    public $timestamps = false;

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'agent_id',
        'interface',
        'bytes_sent',
        'bytes_recv',
        'packets_sent',
        'packets_recv',
        'err_in',
        'err_out',
        'drop_in',
        'drop_out',
        'timestamp',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'bytes_sent' => 'integer',
        'bytes_recv' => 'integer',
        'packets_sent' => 'integer',
        'packets_recv' => 'integer',
        'err_in' => 'integer',
        'err_out' => 'integer',
        'drop_in' => 'integer',
        'drop_out' => 'integer',
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
}
