<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResponseCodeEnum;
use App\Http\Controllers\Controller;
use App\Services\MetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jiannei\Response\Laravel\Support\Facades\Response;

class MetricController extends Controller
{
    public function __construct(
        private readonly MetricService $metricService
    ) {}

    /**
     * 获取指标历史数据
     */
    public function index(Request $request, string $agent_id): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:cpu,memory,disk,network,load,disk_io,gpu,temperature',
            'range' => 'nullable|string|in:1h,6h,12h,24h,7d',
        ]);

        $result = $this->metricService->getHistory(
            $agent_id,
            $validated['type'],
            $validated['range'] ?? '1h'
        );

        if ($result === null) {
            return Response::fail('不支持的指标类型');
        }

        return Response::success($result);
    }

    /**
     * 批量上报指标
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'metrics' => 'required|array',
        ]);

        $result = $this->metricService->storeBatch(
            $validated['agent_id'],
            $validated['metrics']
        );

        return Response::success(['received' => $result['count']], '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }
}
