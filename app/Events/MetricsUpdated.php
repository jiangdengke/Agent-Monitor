<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 指标数据更新事件
 *
 * 当探针上报新的指标数据时触发，通过 WebSocket 推送给前端
 */
class MetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 探针 ID
     */
    public string $agentId;

    /**
     * 指标数据
     */
    public array $metrics;

    /**
     * 创建事件实例
     */
    public function __construct(string $agentId, array $metrics)
    {
        $this->agentId = $agentId;
        $this->metrics = $metrics;
    }

    /**
     * 获取事件广播的频道
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('metrics'),
            new Channel('agent.' . $this->agentId),
        ];
    }

    /**
     * 广播事件名称
     */
    public function broadcastAs(): string
    {
        return 'metrics.updated';
    }

    /**
     * 广播的数据
     */
    public function broadcastWith(): array
    {
        return [
            'agent_id' => $this->agentId,
            'metrics' => $this->metrics,
            'timestamp' => now()->timestamp * 1000,
        ];
    }
}
