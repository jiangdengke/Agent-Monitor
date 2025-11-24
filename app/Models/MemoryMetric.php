<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MemoryMetric 模型
 *
 * 内存性能指标数据
 * 存储物理内存和 Swap 的使用情况
 */
class MemoryMetric extends Model
{
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
        'total',
        'used',
        'free',
        'usage_percent',
        'swap_total',
        'swap_used',
        'swap_free',
        'timestamp',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'total' => 'integer',
        'used' => 'integer',
        'free' => 'integer',
        'usage_percent' => 'float',
        'swap_total' => 'integer',
        'swap_used' => 'integer',
        'swap_free' => 'integer',
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
