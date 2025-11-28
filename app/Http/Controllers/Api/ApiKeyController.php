<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResponseCodeEnum;
use App\Http\Controllers\Controller;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jiannei\Response\Laravel\Support\Facades\Response;

class ApiKeyController extends Controller
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService
    ) {}

    /**
     * 创建 API Key
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'created_by' => 'nullable|string|max:255',
        ]);

        $apiKey = $this->apiKeyService->create($validated);

        return Response::success($apiKey, '', ResponseCodeEnum::API_KEY_CREATE_SUCCESS);
    }

    /**
     * 更新 API Key
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'enabled' => 'boolean',
        ]);

        $apiKey = $this->apiKeyService->update($id, $validated);

        if (!$apiKey) {
            return Response::fail('', ResponseCodeEnum::API_KEY_NOT_FOUND);
        }

        return Response::success($apiKey, '', ResponseCodeEnum::API_KEY_UPDATE_SUCCESS);
    }

    /**
     * 删除 API Key
     */
    public function delete(string $id): JsonResponse
    {
        $deleted = $this->apiKeyService->delete($id);

        if (!$deleted) {
            return Response::fail('', ResponseCodeEnum::API_KEY_NOT_FOUND);
        }

        return Response::success(null, '删除成功', ResponseCodeEnum::API_KEY_DELETE_SUCCESS);
    }

    /**
     * 获取 API Key 详情
     */
    public function show(string $id): JsonResponse
    {
        $apiKey = $this->apiKeyService->find($id);

        if (!$apiKey) {
            return Response::fail('', ResponseCodeEnum::API_KEY_NOT_FOUND);
        }

        return Response::success($apiKey, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取 API Key 列表
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['name', 'enabled']);
        $pageSize = $request->input('pageSize', 10);

        $apiKeys = $this->apiKeyService->list($filters, $pageSize);

        return Response::success([
            'items' => $apiKeys->items(),
            'total' => $apiKeys->total(),
        ], '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 启用 API Key
     */
    public function enable(string $id): JsonResponse
    {
        $apiKey = $this->apiKeyService->enable($id);

        if (!$apiKey) {
            return Response::fail('', ResponseCodeEnum::API_KEY_NOT_FOUND);
        }

        return Response::success($apiKey, '', ResponseCodeEnum::API_KEY_UPDATE_SUCCESS);
    }

    /**
     * 禁用 API Key
     */
    public function disable(string $id): JsonResponse
    {
        $apiKey = $this->apiKeyService->disable($id);

        if (!$apiKey) {
            return Response::fail('', ResponseCodeEnum::API_KEY_NOT_FOUND);
        }

        return Response::success($apiKey, '', ResponseCodeEnum::API_KEY_UPDATE_SUCCESS);
    }

    /**
     * 重新生成 API Key
     */
    public function regenerate(string $id): JsonResponse
    {
        $apiKey = $this->apiKeyService->regenerate($id);

        if (!$apiKey) {
            return Response::fail('', ResponseCodeEnum::API_KEY_NOT_FOUND);
        }

        return Response::success($apiKey, '', ResponseCodeEnum::API_KEY_UPDATE_SUCCESS);
    }
}
