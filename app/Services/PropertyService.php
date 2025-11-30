<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Collection;

class PropertyService
{
    /**
     * 获取配置值
     */
    public function get(string $id, mixed $default = null): mixed
    {
        return Property::getValue($id, $default);
    }

    /**
     * 设置配置值
     */
    public function set(string $id, string $name, mixed $value): Property
    {
        return Property::setValue($id, $name, $value);
    }

    /**
     * 删除配置
     */
    public function delete(string $id): bool
    {
        return Property::deleteValue($id);
    }

    /**
     * 获取所有配置
     */
    public function all(): Collection
    {
        return Property::all();
    }

    /**
     * 批量设置配置
     */
    public function setBatch(array $items): int
    {
        $count = 0;
        foreach ($items as $item) {
            if (isset($item['id']) && isset($item['name']) && isset($item['value'])) {
                Property::setValue($item['id'], $item['name'], $item['value']);
                $count++;
            }
        }
        return $count;
    }
}
