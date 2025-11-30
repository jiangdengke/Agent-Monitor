<?php

namespace App\Console\Commands;

use App\Events\AgentStatusChanged;
use App\Models\Agent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckAgentHeartbeat extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'agent:check-heartbeat {--timeout=120 : 心跳超时秒数}';

    /**
     * 命令描述
     */
    protected $description = '检查探针心跳，将超时的探针标记为离线';

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');
        $thresholdTime = (now()->timestamp - $timeout) * 1000;

        // 查找需要标记为离线的探针
        $offlineAgents = Agent::where('status', 1)
            ->where('last_seen_at', '<', $thresholdTime)
            ->get();

        $count = 0;
        foreach ($offlineAgents as $agent) {
            $agent->status = 0;
            $agent->save();

            // 广播状态变更
            event(new AgentStatusChanged($agent->id, $agent->hostname, 0));

            Log::info("探针已离线: {$agent->hostname} ({$agent->id})");
            $count++;
        }

        if ($count > 0) {
            $this->info("已将 {$count} 个探针标记为离线");
        }

        return Command::SUCCESS;
    }
}
