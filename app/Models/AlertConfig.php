<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * AlertConfig 模型
 *
 * 告警配置
 * 定义 CPU、内存、磁盘等资源的告警阈值和规则
 */
class AlertConfig extends Model
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
        'name',
        'enabled',
        'rule_cpu_enabled',
        'rule_cpu_threshold',
        'rule_cpu_duration',
        'rule_memory_enabled',
        'rule_memory_threshold',
        'rule_memory_duration',
        'rule_disk_enabled',
        'rule_disk_threshold',
        'rule_disk_duration',
        'rule_network_enabled',
        'rule_network_duration',
        'created_at',
        'updated_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'enabled' => 'boolean',
        'rule_cpu_enabled' => 'boolean',
        'rule_cpu_threshold' => 'float',
        'rule_cpu_duration' => 'integer',
        'rule_memory_enabled' => 'boolean',
        'rule_memory_threshold' => 'float',
        'rule_memory_duration' => 'integer',
        'rule_disk_enabled' => 'boolean',
        'rule_disk_threshold' => 'float',
        'rule_disk_duration' => 'integer',
        'rule_network_enabled' => 'boolean',
        'rule_network_duration' => 'integer',
        'created_at' => 'integer',
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
            $now = now()->timestamp * 1000;
            $model->created_at = $now;
            $model->updated_at = $now;
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
     * 告警记录
     */
    public function alertRecords(): HasMany
    {
        return $this->hasMany(AlertRecord::class, 'config_id');
    }
}
