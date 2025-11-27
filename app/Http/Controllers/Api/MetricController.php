<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResponseCodeEnum;
use App\Http\Controllers\Controller;
use App\Models\CpuMetric;
use App\Models\DiskIoMetric;
use App\Models\GpuMetric;
use App\Models\HostMetric;
use App\Models\LoadMetric;
use App\Models\MemoryMetric;
use App\Models\DiskMetric;
use App\Models\MonitorMetric;
use App\Models\NetworkMetric;
use App\Models\TemperatureMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jiannei\Response\Laravel\Support\Facades\Response;

class MetricController extends Controller
{
    /**
     * 获取指标历史数据
     */
    public function index(Request $request, string $agent_id): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:cpu,memory,disk,network,load,disk_io,gpu,temperature',
            'range' => 'nullable|string|in:1h,6h,12h,24h,7d', // 前端传的时间范围
        ]);

        $type = $request->input('type');
        $range = $request->input('range', '1h');

        // 计算开始时间 (毫秒)
        $now = now();
        $startTime = match ($range) {
            '6h' => $now->subHours(6)->timestamp * 1000,
            '12h' => $now->subHours(12)->timestamp * 1000,
            '24h' => $now->subDay()->timestamp * 1000,
            '7d' => $now->subDays(7)->timestamp * 1000,
            default => $now->subHour()->timestamp * 1000,
        };

        // 根据类型选择模型
        $query = match ($type) {
            'cpu' => CpuMetric::query(),
            'memory' => MemoryMetric::query(),
            'disk' => DiskMetric::query(), // 注意：磁盘可能有多个挂载点
            'network' => NetworkMetric::query(), // 注意：网卡可能有多个
            'load' => LoadMetric::query(),
            'disk_io' => DiskIoMetric::query(),
            'gpu' => GpuMetric::query(),
            'temperature' => TemperatureMetric::query(),
            default => null,
        };

        if (!$query) {
            return Response::fail('不支持的指标类型');
        }

        // 执行查询
        // 注意：如果数据量大，这里应该做降采样 (Downsampling)，但第一版先直接查
        $data = $query->where('agent_id', $agent_id)
            ->where('timestamp', '>=', $startTime)
            ->orderBy('timestamp', 'asc') // 按时间正序，方便前端绘图
            ->get();

        // 格式化返回结构，参考前端 GetAgentMetricsResponse
        return Response::success([
            'agentId' => $agent_id,
            'type' => $type,
            'range' => $range,
            'metrics' => $data
        ]);
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

        $agentId = $validated['agent_id'];
        $metrics = $validated['metrics'];
        $count = 0;
        
        Log::info("收到 " . count($metrics) . " 条指标数据，来自 Agent: {$agentId}");

        foreach ($metrics as $metricData) {
            if (!isset($metricData['type']) || !isset($metricData['data'])) {
                continue;
            }

            $type = $metricData['type'];
            $data = $metricData['data'];
            $data['agent_id'] = $agentId;
            // 如果 data 中没有 timestamp，则使用当前时间（外层可能有，或者默认当前）
            if (!isset($data['timestamp'])) {
                 $data['timestamp'] = $metricData['timestamp'] ?? (now()->timestamp * 1000);
            }

            // 记录关键指标的数值方便直观监控
            if ($type === 'cpu') {
                Log::info("CPU 使用率: {$data['usage_percent']}% (Agent: {$agentId})");
            } elseif ($type === 'memory') {
                Log::info("内存使用率: {$data['usage_percent']}% (Agent: {$agentId})");
            }

            try {
                match ($type) {
                    'cpu' => CpuMetric::create($data),
                    'memory' => MemoryMetric::create($data),
                    'disk' => DiskMetric::create($data),
                    'disk_io' => DiskIoMetric::create($data),
                    'network' => NetworkMetric::create($data),
                    'load' => LoadMetric::create($data),
                    'gpu' => GpuMetric::create($data),
                    'temperature' => TemperatureMetric::create($data),
                    'host' => HostMetric::create($data),
                    'monitor' => MonitorMetric::create($data),
                    default => null,
                };
                $count++;
            } catch (\Exception $e) {
                // 记录写入错误
                Log::warning("指标写入失败 [类型: {$type}]: " . $e->getMessage());
            }
        }
        
        Log::info("成功存储 {$count} 条指标数据 (Agent: {$agentId})");

        return Response::success(['received' => $count], '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }

    /**
     * 上报CPU指标
     */
    public function storeCpu(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'usage_percent' => 'required|numeric|min:0|max:100',
            'logical_cores' => 'nullable|integer|min:1',
            'physical_cores' => 'nullable|integer|min:1',
            'model_name' => 'nullable|string|max:255',
            'timestamp' => 'required|integer',
        ]);

        $metric = CpuMetric::create($validated);
        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }

    /**
     * 上报内存指标
     */
    public function storeMemory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'total' => 'required|integer|min:0',
            'used' => 'required|integer|min:0',
            'free' => 'required|integer|min:0',
            'usage_percent' => 'required|numeric|min:0|max:100',
            'swap_total' => 'nullable|integer|min:0',
            'swap_used' => 'nullable|integer|min:0',
            'swap_free' => 'nullable|integer|min:0',
            'timestamp' => 'required|integer',
        ]);
        $metric = MemoryMetric::create($validated);

        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }

    /**
     * 上报磁盘指标
     */

    public function storeDisk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'mount_point' => 'required|string|max:255',
            'total' => 'required|integer|min:0',
            'used' => 'required|integer|min:0',
            'free' => 'required|integer|min:0',
            'usage_percent' => 'required|numeric|min:0|max:100',
            'timestamp' => 'required|integer',
        ]);
        $metric = DiskMetric::create($validated);

        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }

    /**
     * 上报磁盘IO指标
     */
    public function storeDiskIo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'device' => 'required|string|max:255',
            'read_bytes' => 'nullable|integer|min:0',
            'write_bytes' => 'nullable|integer|min:0',
            'read_count' => 'nullable|integer|min:0',
            'write_count' => 'nullable|integer|min:0',
            'read_time' => 'nullable|integer|min:0',
            'write_time' => 'nullable|integer|min:0',
            'read_bytes_rate' => 'nullable|integer|min:0',
            'write_bytes_rate' => 'nullable|integer|min:0',
            'io_time' => 'nullable|integer|min:0',
            'iops_in_progress' => 'nullable|integer|min:0',
            'timestamp' => 'required|integer',
        ]);

        $metric = DiskIoMetric::create($validated);

        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }

    /**
     * 上报网络指标
     */
    public function storeNetwork(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'interface' => 'required|string|max:255',
            'bytes_sent_rate' => 'nullable|integer|min:0',
            'bytes_recv_rate' => 'nullable|integer|min:0',
            'bytes_sent_total' => 'nullable|integer|min:0',
            'bytes_recv_total' => 'nullable|integer|min:0',
            'timestamp' => 'required|integer',
        ]);

        $metric = NetworkMetric::create($validated);
        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }

    /**
     * 上报负载指标
     *
     */
    public function storeLoad(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'load1' => 'required|numeric|min:0',
            'load5' => 'required|numeric|min:0',
            'load15' => 'required|numeric|min:0',
            'timestamp' => 'required|integer',
        ]);

        $metric = LoadMetric::create($validated);
        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }

    /**
     * 上传GPU指标
     */
    public function storeGpu(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'index' => 'required|integer|min:0',
            'name' => 'nullable|string|max:255',
            'utilization' => 'nullable|numeric|min:0|max:100',
            'memory_total' => 'nullable|integer|min:0',
            'memory_used' => 'nullable|integer|min:0',
            'memory_free' => 'nullable|integer|min:0',
            'temperature' => 'nullable|numeric|min:0',
            'power_draw' => 'nullable|numeric|min:0',
            'fan_speed' => 'nullable|numeric|min:0|max:100',
            'performance_state' => 'nullable|string|max:50',
            'timestamp' => 'required|integer',
        ]);

        $metric = GpuMetric::create($validated);

        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }

    /**
     * 上报温度指标
     */
    public function storeTemperature(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'sensor_key' => 'required|string|max:255',
            'sensor_label' => 'nullable|string|max:255',
            'temperature' => 'required|numeric',
            'timestamp' => 'required|integer',
        ]);

        $metric = TemperatureMetric::create($validated);

        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }


    /**
     * 上报主机信息
     */

    public function storeHost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'os' => 'nullable|string|max:50',
            'platform' => 'nullable|string|max:50',
            'platform_version' => 'nullable|string|max:50',
            'kernel_version' => 'nullable|string|max:100',
            'kernel_arch' => 'nullable|string|max:50',
            'uptime' => 'nullable|integer|min:0',
            'boot_time' => 'nullable|integer|min:0',
            'procs' => 'nullable|integer|min:0',
            'timestamp' => 'required|integer',
        ]);

        $metric = HostMetric::create($validated);
        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }

    /**
     * 上报监控指标
     */

    public function storeMonitor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid|exists:agents,id',
            'monitor_id' => 'required|string|max:255',
            'type' => 'required|string|in:http,tcp',
            'target' => 'required|string|max:500',
            'status' => 'required|string|in:up,down',
            'status_code' => 'nullable|integer',
            'response_time' => 'nullable|integer|min:0',
            'error' => 'nullable|string',
            'message' => 'nullable|string',
            'content_match' => 'nullable|boolean',
            'cert_expiry_time' => 'nullable|integer',
            'cert_days_left' => 'nullable|integer',
            'timestamp' => 'required|integer',
        ]);

        $metric = MonitorMetric::create($validated);

        return Response::success($metric, '', ResponseCodeEnum::METRIC_SAVE_SUCCESS);
    }
}
