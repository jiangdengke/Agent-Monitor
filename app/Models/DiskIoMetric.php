<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DiskIoMetric 模型
 *
 * 磁盘 I/O 性能指标
 * 存储磁盘读写速率和 IOPS 信息
 */
class DiskIoMetric extends Model{
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
        'device',
        'read_count',
        'write_count',
        'read_bytes',
        'write_bytes',
        'read_bytes_rate',
        'write_bytes_rate',
        'read_time',
        'write_time',
        'io_time',
        'iops_in_progress',
        'timestamp',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'read_count' => 'integer',
        'write_count' => 'integer',
        'read_bytes' => 'integer',
        'write_bytes' => 'integer',
        'read_bytes_rate' => 'integer',
        'write_bytes_rate' => 'integer',
        'read_time' => 'integer',
        'write_time' => 'integer',
        'io_time' => 'integer',
        'iops_in_progress' => 'integer',
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
