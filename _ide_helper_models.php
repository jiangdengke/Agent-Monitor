<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * Agent 模型
 * 
 * 探针（监控客户端）信息管理
 * 存储探针的基本信息和运行状态
 *
 * @property string $id
 * @property string $name
 * @property string|null $hostname
 * @property string|null $ip
 * @property string|null $os
 * @property string|null $arch
 * @property string|null $version
 * @property string|null $platform
 * @property string|null $location
 * @property int|null $expire_time
 * @property int $status
 * @property int $last_seen_at
 * @property int $created_at
 * @property int $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AlertConfig> $alertConfigs
 * @property-read int|null $alert_configs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AlertRecord> $alertRecords
 * @property-read int|null $alert_records_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AuditResult> $auditResults
 * @property-read int|null $audit_results_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CpuMetric> $cpuMetrics
 * @property-read int|null $cpu_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DiskIoMetric> $diskIoMetrics
 * @property-read int|null $disk_io_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DiskMetric> $diskMetrics
 * @property-read int|null $disk_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GpuMetric> $gpuMetrics
 * @property-read int|null $gpu_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HostMetric> $hostMetrics
 * @property-read int|null $host_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoadMetric> $loadMetrics
 * @property-read int|null $load_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MemoryMetric> $memoryMetrics
 * @property-read int|null $memory_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MonitorMetric> $monitorMetrics
 * @property-read int|null $monitor_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MonitorStats> $monitorStats
 * @property-read int|null $monitor_stats_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NetworkMetric> $networkMetrics
 * @property-read int|null $network_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TemperatureMetric> $temperatureMetrics
 * @property-read int|null $temperature_metrics_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereArch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereExpireTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereHostname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereVersion($value)
 */
	class Agent extends \Eloquent {}
}

namespace App\Models{
/**
 * AlertConfig 模型
 * 
 * 告警规则配置
 * 定义触发告警的条件和通知方式
 *
 * @property string $id
 * @property string|null $agent_id
 * @property string $name
 * @property bool $enabled
 * @property bool $rule_cpu_enabled
 * @property float|null $rule_cpu_threshold
 * @property int|null $rule_cpu_duration
 * @property bool $rule_memory_enabled
 * @property float|null $rule_memory_threshold
 * @property int|null $rule_memory_duration
 * @property bool $rule_disk_enabled
 * @property float|null $rule_disk_threshold
 * @property int|null $rule_disk_duration
 * @property bool $rule_network_enabled
 * @property int|null $rule_network_duration
 * @property int $created_at
 * @property int $updated_at
 * @property-read \App\Models\Agent|null $agent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AlertRecord> $alertRecords
 * @property-read int|null $alert_records_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleCpuDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleCpuEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleCpuThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleDiskDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleDiskEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleDiskThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleMemoryDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleMemoryEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleMemoryThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleNetworkDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereRuleNetworkEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertConfig whereUpdatedAt($value)
 */
	class AlertConfig extends \Eloquent {}
}

namespace App\Models{
/**
 * AlertRecord 模型
 * 
 * 告警历史记录
 * 记录每次触发的告警事件
 *
 * @property int $id
 * @property string $agent_id
 * @property string $config_id
 * @property string $config_name
 * @property string $alert_type
 * @property string $message
 * @property float|null $threshold
 * @property float|null $actual_value
 * @property string $level
 * @property string $status
 * @property int $fired_at
 * @property int|null $resolved_at
 * @property int $created_at
 * @property int $updated_at
 * @property-read \App\Models\Agent $agent
 * @property-read \App\Models\AlertConfig|null $alertConfig
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereActualValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereAlertType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereConfigId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereConfigName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereFiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertRecord whereUpdatedAt($value)
 */
	class AlertRecord extends \Eloquent {}
}

namespace App\Models{
/**
 * ApiKey 模型
 * 
 * 管理系统的 API 访问密钥
 * Agent 通过 API Key 进行身份认证和数据上报
 *
 * @property string $id
 * @property string $name
 * @property string $key
 * @property bool $enabled
 * @property string $created_by
 * @property int $created_at
 * @property int $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereUpdatedAt($value)
 */
	class ApiKey extends \Eloquent {}
}

namespace App\Models{
/**
 * AuditResult 模型
 * 
 * 系统审计结果
 * 存储安全扫描或配置检查的结果
 *
 * @property int $id
 * @property string $agent_id
 * @property string $type
 * @property string $result
 * @property int $start_time
 * @property int $end_time
 * @property int $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditResult whereType($value)
 */
	class AuditResult extends \Eloquent {}
}

namespace App\Models{
/**
 * CpuMetric 模型
 * 
 * CPU 性能指标数据
 * 存储 CPU 使用率、频率等性能信息
 *
 * @property int $id
 * @property string $agent_id
 * @property float $usage_percent
 * @property int|null $logical_cores
 * @property int|null $physical_cores
 * @property string|null $model_name
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric whereLogicalCores($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric wherePhysicalCores($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CpuMetric whereUsagePercent($value)
 */
	class CpuMetric extends \Eloquent {}
}

namespace App\Models{
/**
 * DiskIoMetric 模型
 * 
 * 磁盘 I/O 性能指标
 * 存储磁盘读写速率和 IOPS 信息
 *
 * @property int $id
 * @property string $agent_id
 * @property string $device
 * @property int|null $read_count
 * @property int|null $write_count
 * @property int|null $read_bytes
 * @property int|null $write_bytes
 * @property int|null $read_bytes_rate
 * @property int|null $write_bytes_rate
 * @property int|null $read_time
 * @property int|null $write_time
 * @property int|null $io_time
 * @property int|null $iops_in_progress
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereIoTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereIopsInProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereReadBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereReadBytesRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereReadCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereReadTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereWriteBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereWriteBytesRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereWriteCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskIoMetric whereWriteTime($value)
 */
	class DiskIoMetric extends \Eloquent {}
}

namespace App\Models{
/**
 * DiskMetric 模型
 * 
 * 磁盘空间使用指标
 * 存储磁盘分区的容量和使用情况
 *
 * @property int $id
 * @property string $agent_id
 * @property string $mount_point
 * @property int $total
 * @property int $used
 * @property int $free
 * @property float $usage_percent
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric whereFree($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric whereMountPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric whereUsagePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiskMetric whereUsed($value)
 */
	class DiskMetric extends \Eloquent {}
}

namespace App\Models{
/**
 * GpuMetric 模型
 * 
 * GPU 性能指标
 * 存储 GPU 使用率和状态信息
 *
 * @property int $id
 * @property string $agent_id
 * @property int $index
 * @property string|null $name
 * @property float|null $utilization
 * @property int|null $memory_total
 * @property int|null $memory_used
 * @property int|null $memory_free
 * @property float|null $temperature
 * @property float|null $power_draw
 * @property float|null $fan_speed
 * @property string|null $performance_state
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereFanSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereMemoryFree($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereMemoryTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereMemoryUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric wherePerformanceState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric wherePowerDraw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GpuMetric whereUtilization($value)
 */
	class GpuMetric extends \Eloquent {}
}

namespace App\Models{
/**
 * HostMetric 模型
 * 
 * 主机系统信息
 * 存储操作系统和运行时信息
 *
 * @property int $id
 * @property string $agent_id
 * @property string|null $os
 * @property string|null $platform
 * @property string|null $platform_version
 * @property string|null $kernel_version
 * @property string|null $kernel_arch
 * @property int|null $uptime
 * @property int|null $boot_time
 * @property int|null $procs
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereBootTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereKernelArch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereKernelVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric wherePlatformVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereProcs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HostMetric whereUptime($value)
 */
	class HostMetric extends \Eloquent {}
}

namespace App\Models{
/**
 * LoadMetric 模型
 * 
 * 系统负载指标
 * 存储系统平均负载数据（1、5、15 分钟）
 *
 * @property int $id
 * @property string $agent_id
 * @property float $load1
 * @property float $load5
 * @property float $load15
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric whereLoad1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric whereLoad15($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric whereLoad5($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoadMetric whereTimestamp($value)
 */
	class LoadMetric extends \Eloquent {}
}

namespace App\Models{
/**
 * MemoryMetric 模型
 * 
 * 内存性能指标数据
 * 存储物理内存和 Swap 的使用情况
 *
 * @property int $id
 * @property string $agent_id
 * @property int $total
 * @property int $used
 * @property int $free
 * @property float $usage_percent
 * @property int|null $swap_total
 * @property int|null $swap_used
 * @property int|null $swap_free
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereFree($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereSwapFree($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereSwapTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereSwapUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereUsagePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemoryMetric whereUsed($value)
 */
	class MemoryMetric extends \Eloquent {}
}

namespace App\Models{
/**
 * MonitorMetric 模型
 * 
 * 服务监控检测结果
 * 存储 HTTP/TCP 等服务监控的详细检测结果
 *
 * @property int $id
 * @property string $agent_id
 * @property string $monitor_id
 * @property string $type
 * @property string $target
 * @property string $status
 * @property int|null $status_code
 * @property int|null $response_time
 * @property string|null $error
 * @property string|null $message
 * @property bool|null $content_match
 * @property int|null $cert_expiry_time
 * @property int|null $cert_days_left
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @property-read \App\Models\MonitorTask|null $monitorTask
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereCertDaysLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereCertExpiryTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereContentMatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereMonitorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereResponseTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorMetric whereType($value)
 */
	class MonitorMetric extends \Eloquent {}
}

namespace App\Models{
/**
 * MonitorStats 模型
 * 
 * 监控任务统计数据
 * 聚合每个探针执行监控任务的成功/失败统计
 *
 * @property int $id
 * @property string $agent_id
 * @property string $monitor_id
 * @property string $monitor_type
 * @property string $target
 * @property int|null $current_response
 * @property int|null $avg_response_24h
 * @property float|null $uptime_24h
 * @property float|null $uptime_30d
 * @property int|null $cert_expiry_date
 * @property int|null $cert_expiry_days
 * @property int|null $total_checks_24h
 * @property int|null $success_checks_24h
 * @property int|null $total_checks_30d
 * @property int|null $success_checks_30d
 * @property int|null $last_check_time
 * @property string|null $last_check_status
 * @property int $updated_at
 * @property-read \App\Models\Agent $agent
 * @property-read \App\Models\MonitorTask|null $monitorTask
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereAvgResponse24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereCertExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereCertExpiryDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereCurrentResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereLastCheckStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereLastCheckTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereMonitorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereMonitorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereSuccessChecks24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereSuccessChecks30d($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereTotalChecks24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereTotalChecks30d($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereUptime24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorStats whereUptime30d($value)
 */
	class MonitorStats extends \Eloquent {}
}

namespace App\Models{
/**
 * MonitorTask 模型
 * 
 * 监控任务配置
 * 定义服务监控任务的目标、频率和验证规则
 *
 * @property string $id
 * @property string $name
 * @property string $type
 * @property string $target
 * @property string|null $description
 * @property bool $enabled
 * @property bool $show_target_public
 * @property int $interval
 * @property array<array-key, mixed>|null $agent_ids
 * @property string|null $http_config
 * @property string|null $tcp_config
 * @property int $created_at
 * @property int $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MonitorMetric> $monitorMetrics
 * @property-read int|null $monitor_metrics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MonitorStats> $monitorStats
 * @property-read int|null $monitor_stats_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereAgentIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereHttpConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereShowTargetPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereTcpConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonitorTask whereUpdatedAt($value)
 */
	class MonitorTask extends \Eloquent {}
}

namespace App\Models{
/**
 * NetworkMetric 模型
 * 
 * 网络流量指标
 * 存储网卡的收发数据量和数据包统计
 *
 * @property int $id
 * @property string $agent_id
 * @property string $interface
 * @property int|null $bytes_sent_rate
 * @property int|null $bytes_recv_rate
 * @property int|null $bytes_sent_total
 * @property int|null $bytes_recv_total
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric whereBytesRecvRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric whereBytesRecvTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric whereBytesSentRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric whereBytesSentTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric whereInterface($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NetworkMetric whereTimestamp($value)
 */
	class NetworkMetric extends \Eloquent {}
}

namespace App\Models{
/**
 * Property 模型
 * 
 * 系统配置表
 * 存储键值对格式的系统配置信息
 *
 * @property string $id
 * @property string $name
 * @property string $value
 * @property int $created_at
 * @property int $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Property whereValue($value)
 */
	class Property extends \Eloquent {}
}

namespace App\Models{
/**
 * TemperatureMetric 模型
 * 
 * 温度传感器指标
 * 存储各类硬件温度数据
 *
 * @property int $id
 * @property string $agent_id
 * @property string $sensor_key
 * @property string|null $sensor_label
 * @property float $temperature
 * @property int $timestamp
 * @property int|null $created_at
 * @property-read \App\Models\Agent $agent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric whereSensorKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric whereSensorLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemperatureMetric whereTimestamp($value)
 */
	class TemperatureMetric extends \Eloquent {}
}

