<?php

namespace App\Services;

use App\Models\ApiKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiKeyService
{
    /**
     * 创建 API Key
     */
    public function create(array $data): ApiKey
    {
        return ApiKey::generateKey(
            $data['name'],
            $data['created_by'] ?? 'system'
        );
    }

    /**
     * 更新 API Key
     */
    public function update(string $id, array $data): ?ApiKey
    {
        $apiKey = ApiKey::find($id);
        if (!$apiKey) {
            return null;
        }

        $apiKey->update($data);
        return $apiKey;
    }

    /**
     * 删除 API Key
     */
    public function delete(string $id): bool
    {
        $apiKey = ApiKey::find($id);
        if (!$apiKey) {
            return false;
        }

        $apiKey->delete();
        return true;
    }

    /**
     * 获取 API Key 详情
     */
    public function find(string $id): ?ApiKey
    {
        return ApiKey::find($id);
    }

    /**
     * 获取 API Key 列表
     */
    public function list(array $filters, int $pageSize = 10): LengthAwarePaginator
    {
        $query = ApiKey::query();

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if (isset($filters['enabled'])) {
            $query->where('enabled', $filters['enabled']);
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($pageSize);
    }

    /**
     * 启用 API Key
     */
    public function enable(string $id): ?ApiKey
    {
        return $this->update($id, ['enabled' => true]);
    }

    /**
     * 禁用 API Key
     */
    public function disable(string $id): ?ApiKey
    {
        return $this->update($id, ['enabled' => false]);
    }

    /**
     * 重新生成 API Key
     */
    public function regenerate(string $id): ?ApiKey
    {
        $apiKey = ApiKey::find($id);
        if (!$apiKey) {
            return null;
        }

        $apiKey->key = 'ak_' . \Illuminate\Support\Str::random(32);
        $apiKey->save();

        return $apiKey;
    }
}
