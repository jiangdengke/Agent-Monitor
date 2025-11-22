<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CpuMetric 模型
 *
 * CPU 性能指标数据
 * 存储 CPU 使用率、频率等性能信息
 */
class CpuMetric extends Model
{
    // 禁用 Laravel 自动时间戳管理
    public $timestamps = false;

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'agent_id',
        'usage',
        'cores',
        'frequency',
        'timestamp',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'usage' => 'float',
        'cores' => 'integer',
        'frequency' => 'float',
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
