<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * ApiKey 模型
 *
 * 管理系统的 API 访问密钥
 * Agent 通过 API Key 进行身份认证和数据上报
 */
class ApiKey extends Model
{
    // 使用字符串类型主键（UUID）
    protected $keyType = 'string';
    public $incrementing = false;

    // 禁用 Laravel 自动时间戳管理，使用自定义毫秒时间戳
    public $timestamps = false;

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'id',
        'name',
        'key',
        'description',
        'expires_at',
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'expires_at' => 'integer',
        'last_used_at' => 'integer',
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
     * 检查 API Key 是否有效
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // 检查是否过期
        if ($this->expires_at && $this->expires_at < now()->timestamp * 1000) {
            return false;
        }
        return true;
    }

    /**
     * 生成新的 API Key
     *
     * @param string $name
     * @param string|null $description
     * @param int|null $expiresAt 过期时间（毫秒时间戳）
     * @return self
     */
    public static function generateKey(string $name, ?string $description = null, ?int $expiresAt = null): self
    {
        return self::create([
            'name' => $name,
            'key' => 'ak_' . Str::random(32),
            'description' => $description,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * 更新最后使用时间
     */
    public function updateLastUsed(): void
    {
        $this->last_used_at = now()->timestamp * 1000;
        $this->save();
    }
}
