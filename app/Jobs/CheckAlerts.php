<?php

namespace App\Jobs;

use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $agentId;
    protected array $metricsSnapshot;

    /**
     * Create a new job instance.
     * 
     * @param string $agentId
     * @param array $metricsSnapshot 包含 ['cpu' => 12.5, 'memory' => 40.0, 'disk' => 50.0]
     */
    public function __construct(string $agentId, array $metricsSnapshot)
    {
        $this->agentId = $agentId;
        $this->metricsSnapshot = $metricsSnapshot;
    }

    /**
     * Execute the job.
     */
    public function handle(AlertService $alertService): void
    {
        $alertService->checkMetrics(
            $this->agentId,
            $this->metricsSnapshot['cpu'] ?? null,
            $this->metricsSnapshot['memory'] ?? null,
            $this->metricsSnapshot['disk'] ?? null
        );
    }
}
