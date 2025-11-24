<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AlertRecord 模型
 *
 * 告警历史记录
 * 记录每次触发的告警事件
 */
class AlertRecord extends Model
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
        'config_id',
        'config_name',
        'alert_type',
        'message',
        'threshold',
        'actual_value',
        'level',
        'status',
        'fired_at',
        'resolved_at',
        'created_at',
        'updated_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'threshold' => 'float',
        'actual_value' => 'float',
        'fired_at' => 'integer',
        'resolved_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    /**
     * 模型启动方法
     * 自动设置毫秒时间戳
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
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
     * 关联的告警配置
     */
    public function alertConfig(): BelongsTo
    {
        return $this->belongsTo(AlertConfig::class, 'config_id');
    }

    /**
     * 检查告警是否已解决
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved' && $this->resolved_at !== null;
    }

    /**
     * 标记告警为已解决
     */
    public function markAsResolved(): void
    {
        $this->status = 'resolved';
        $this->resolved_at = now()->timestamp * 1000;
        $this->save();
    }

    /**
     * 获取告警级别文本
     *
     * @return string
     */
    public function getLevelText(): string
    {
        return match($this->level) {
            'info' => '信息',
            'warning' => '警告',
            'critical' => '严重',
            default => '未知',
        };
    }
}
