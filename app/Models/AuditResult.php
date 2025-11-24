<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditResult 模型
 *
 * 系统审计结果
 * 存储安全扫描或配置检查的结果
 */
class AuditResult extends Model
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
        'type',
        'result',
        'start_time',
        'end_time',
        'created_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'start_time' => 'integer',
        'end_time' => 'integer',
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

    /**
     * 获取解析后的结果
     *
     * @return array|null
     */
    public function getParsedResult(): ?array
    {
        if (empty($this->result)) {
            return null;
        }

        $decoded = json_decode($this->result, true);
        return is_array($decoded) ? $decoded : null;
    }
}
