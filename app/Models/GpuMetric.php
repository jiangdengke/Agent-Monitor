<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GpuMetric 模型
 *
 * GPU 性能指标
 * 存储 GPU 使用率和状态信息
 */
class GpuMetric extends Model{
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
        'index',
        'name',
        'utilization',
        'memory_total',
        'memory_used',
        'memory_free',
        'temperature',
        'power_draw',
        'fan_speed',
        'performance_state',
        'timestamp',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'index' => 'integer',
        'utilization' => 'float',
        'memory_total' => 'integer',
        'memory_used' => 'integer',
        'memory_free' => 'integer',
        'temperature' => 'float',
        'power_draw' => 'float',
        'fan_speed' => 'float',
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
