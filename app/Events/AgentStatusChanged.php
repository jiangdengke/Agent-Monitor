<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 探针状态变更事件
 *
 * 当探针上线或下线时触发
 */
class AgentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 探针 ID
     */
    public string $agentId;

    /**
     * 探针名称
     */
    public string $hostname;

    /**
     * 状态 (0=离线, 1=在线)
     */
    public int $status;

    /**
     * 创建事件实例
     */
    public function __construct(string $agentId, string $hostname, int $status)
    {
        $this->agentId = $agentId;
        $this->hostname = $hostname;
        $this->status = $status;
    }

    /**
     * 获取事件广播的频道
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('agents'),
        ];
    }

    /**
     * 广播事件名称
     */
    public function broadcastAs(): string
    {
        return 'agent.status';
    }

    /**
     * 广播的数据
     */
    public function broadcastWith(): array
    {
        return [
            'agent_id' => $this->agentId,
            'hostname' => $this->hostname,
            'status' => $this->status,
            'timestamp' => now()->timestamp * 1000,
        ];
    }
}
