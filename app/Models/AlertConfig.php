<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * AlertConfig 模型
 *
 * 告警规则配置
 * 定义触发告警的条件和通知方式
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
        'type',
        'metric',
        'operator',
        'threshold',
        'duration',
        'enabled',
        'notify_channels',
        'created_at',
        'updated_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'threshold' => 'float',
        'duration' => 'integer',
        'enabled' => 'boolean',
        'notify_channels' => 'array', // JSON 数组
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

    /**
     * 检查值是否触发告警
     *
     * @param float $value
     * @return bool
     */
    public function shouldAlert(float $value): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return match($this->operator) {
            '>' => $value > $this->threshold,
            '>=' => $value >= $this->threshold,
            '<' => $value < $this->threshold,
            '<=' => $value <= $this->threshold,
            '==' => $value == $this->threshold,
            '!=' => $value != $this->threshold,
            default => false,
        };
    }
}
