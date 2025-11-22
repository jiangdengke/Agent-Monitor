<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HostMetric 模型
 *
 * 主机系统信息
 * 存储操作系统和运行时信息
 */
class HostMetric extends Model
{
    // 禁用 Laravel 自动时间戳管理
    public $timestamps = false;

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'agent_id',
        'os',
        'platform',
        'platform_version',
        'kernel_version',
        'kernel_arch',
        'uptime',
        'boot_time',
        'procs',
        'timestamp',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'uptime' => 'integer',
        'boot_time' => 'integer',
        'procs' => 'integer',
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
