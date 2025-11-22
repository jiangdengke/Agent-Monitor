<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Property 模型
 *
 * 系统配置表
 * 存储键值对格式的系统配置信息
 */
class Property extends Model
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
        'key',
        'value',
        'description',
        'created_at',
        'updated_at',
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
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
     * 根据 key 获取配置值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $property = self::where('key', $key)->first();
        return $property ? $property->value : $default;
    }

    /**
     * 设置配置值
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @return self
     */
    public static function setValue(string $key, mixed $value, ?string $description = null): self
    {
        $property = self::where('key', $key)->first();

        if ($property) {
            $property->value = $value;
            if ($description !== null) {
                $property->description = $description;
            }
            $property->save();
        } else {
            $property = self::create([
                'key' => $key,
                'value' => $value,
                'description' => $description,
            ]);
        }

        return $property;
    }

    /**
     * 删除配置
     *
     * @param string $key
     * @return bool
     */
    public static function deleteValue(string $key): bool
    {
        return self::where('key', $key)->delete() > 0;
    }

    /**
     * 检查配置是否存在
     *
     * @param string $key
     * @return bool
     */
    public static function hasKey(string $key): bool
    {
        return self::where('key', $key)->exists();
    }
}
