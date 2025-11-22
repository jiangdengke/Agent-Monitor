<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * AuditResult 模型
 *
 * 系统审计结果
 * 存储安全扫描或配置检查的结果
 */
class AuditResult extends Model
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
        'audit_type',
        'category',
        'title',
        'description',
        'severity',
        'status',
        'recommendation',
        'details',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'details' => 'array', // JSON 对象
        'created_at' => 'integer',
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

    /**
     * 获取严重程度文本
     *
     * @return string
     */
    public function getSeverityText(): string
    {
        return match($this->severity) {
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'critical' => '严重',
            default => '未知',
        };
    }

    /**
     * 获取状态文本
     *
     * @return string
     */
    public function getStatusText(): string
    {
        return match($this->status) {
            'pass' => '通过',
            'fail' => '失败',
            'warning' => '警告',
            'info' => '信息',
            default => '未知',
        };
    }

    /**
     * 检查是否需要关注
     *
     * @return bool
     */
    public function needsAttention(): bool
    {
        return in_array($this->severity, ['high', 'critical'])
            && in_array($this->status, ['fail', 'warning']);
    }
}
