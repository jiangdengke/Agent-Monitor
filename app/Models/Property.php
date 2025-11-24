<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Property 模型
 *
 * 系统配置表
 * 存储键值对格式的系统配置信息
 */
class Property extends Model
{
    // 使用字符串类型主键
    protected $keyType = 'string';
    public $incrementing = false;

    // 禁用 Laravel 自动时间戳管理
    public $timestamps = false;

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'id',
        'name',
        'value',
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
     * 根据 id 获取配置值
     *
     * @param string $id
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $id, mixed $default = null): mixed
    {
        $property = self::find($id);
        return $property ? $property->value : $default;
    }

    /**
     * 设置配置值
     *
     * @param string $id
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public static function setValue(string $id, string $name, mixed $value): self
    {
        $property = self::find($id);

        if ($property) {
            $property->name = $name;
            $property->value = $value;
            $property->save();
        } else {
            $property = self::create([
                'id' => $id,
                'name' => $name,
                'value' => $value,
            ]);
        }

        return $property;
    }

    /**
     * 删除配置
     *
     * @param string $id
     * @return bool
     */
    public static function deleteValue(string $id): bool
    {
        $property = self::find($id);
        return $property ? $property->delete() : false;
    }

    /**
     * 检查配置是否存在
     *
     * @param string $id
     * @return bool
     */
    public static function hasKey(string $id): bool
    {
        return self::where('id', $id)->exists();
    }
}
