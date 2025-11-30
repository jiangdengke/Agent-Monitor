<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResponseCodeEnum;
use App\Http\Controllers\Controller;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jiannei\Response\Laravel\Support\Facades\Response;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService
    ) {}

    /**
     * 获取所有配置
     */
    public function index(): JsonResponse
    {
        $properties = $this->propertyService->all();

        return Response::success($properties, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 获取单个配置
     */
    public function show(string $id): JsonResponse
    {
        $value = $this->propertyService->get($id);

        if ($value === null) {
            return Response::fail('', ResponseCodeEnum::NOT_FOUND);
        }

        return Response::success(['id' => $id, 'value' => $value], '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 设置配置
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'value' => 'required|string',
        ]);

        $property = $this->propertyService->set(
            $validated['id'],
            $validated['name'],
            $validated['value']
        );

        return Response::success($property, '', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 删除配置
     */
    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->propertyService->delete($id);

        if (!$deleted) {
            return Response::fail('', ResponseCodeEnum::NOT_FOUND);
        }

        return Response::success(null, '删除成功', ResponseCodeEnum::SUCCESS);
    }

    /**
     * 批量设置配置
     */
    public function batchStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string|max:255',
            'items.*.name' => 'required|string|max:255',
            'items.*.value' => 'required|string',
        ]);

        $count = $this->propertyService->setBatch($validated['items']);

        return Response::success(['count' => $count], '', ResponseCodeEnum::SUCCESS);
    }
}
