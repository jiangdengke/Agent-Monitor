# 模型代码参考

> 所有模型的完整代码，包括关系定义和辅助方法

---

## 📊 数据库关系说明

### 一对多关系（One to Many）

| 父表 | 子表 | 关系说明 |
|------|------|----------|
| **agents** | cpu_metrics | 一个探针有多条 CPU 指标记录 |
| **agents** | memory_metrics | 一个探针有多条内存指标记录 |
| **agents** | disk_metrics | 一个探针有多条磁盘指标记录 |
| **agents** | disk_io_metrics | 一个探针有多条磁盘 IO 指标记录 |
| **agents** | network_metrics | 一个探针有多条网络指标记录 |
| **agents** | load_metrics | 一个探针有多条负载指标记录 |
| **agents** | gpu_metrics | 一个探针有多条 GPU 指标记录 |
| **agents** | temperature_metrics | 一个探针有多条温度指标记录 |
| **agents** | host_metrics | 一个探针有多条主机信息记录 |
| **agents** | monitor_metrics | 一个探针有多条监控检测结果 |
| **agents** | monitor_stats | 一个探针有多条监控统计数据 |
| **agents** | alert_configs | 一个探针有多个告警配置 |
| **agents** | alert_records | 一个探针有多条告警记录 |
| **agents** | audit_results | 一个探针有多条审计结果 |
| **monitor_tasks** | monitor_metrics | 一个监控任务有多次检测结果 |
| **monitor_tasks** | monitor_stats | 一个监控任务有对应的统计数据 |
| **alert_configs** | alert_records | 一个告警配置可以触发多次告警 |

### 多对多关系（Many to Many）

| 表 A | 表 B | 实现方式 | 说明 |
|------|------|----------|------|
| **agents** | **monitor_tasks** | JSON 字段 | 通过 `monitor_tasks.agent_ids` JSON 数组实现<br>一个探针可以执行多个监控任务<br>一个监控任务可以被多个探针执行 |

### 独立表（无关系）

- **api_keys** - 独立的 API 密钥表，不属于某个探针
- **properties** - 独立的系统配置表

---

## 1. ApiKey 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * API Key 模型
 *
 * 用于管理系统的 API 访问密钥
 * Agent 通过 API Key 进行身份认证
 */
class ApiKey extends Model
{
    // === 基本配置 ===
    protected $table = 'api_keys';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // 使用自定义时间戳字段

    // === 可填充字段 ===
    protected $fillable = [
        'id',
        'name',
        'key',
        'enabled',
        'created_by',
        'created_at',
        'updated_at',
    ];

    // === 字段类型转换 ===
    protected $casts = [
        'enabled' => 'boolean',
        'created_at' => 'integer', // 毫秒时间戳
        'updated_at' => 'integer', // 毫秒时间戳
    ];

    // === 隐藏字段（API 返回时不显示）===
    protected $hidden = [
        'key', // API Key 密钥应该隐藏
    ];

    // === 模型事件 ===
    protected static function boot()
    {
        parent::boot();

        // 创建时自动生成 ID 和时间戳
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now()->timestamp * 1000;
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now()->timestamp * 1000;
            }
        });

        // 更新时自动更新时间戳
        static::updating(function ($model) {
            $model->updated_at = now()->timestamp * 1000;
        });
    }

    // === 辅助方法 ===

    /**
     * 检查 API Key 是否有效
     */
    public function isValid(): bool
    {
        return $this->enabled;
    }

    /**
     * 生成随机 API Key
     */
    public static function generateKey(): string
    {
        return Str::random(32);
    }
}
```

---

## 2. Agent 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Agent 探针模型
 *
 * 存储所有监控探针的基本信息和在线状态
 * 探针是部署在各个服务器上的监控 Agent 程序
 */
class Agent extends Model
{
    // === 基本配置 ===
    protected $table = 'agents';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    // === 可填充字段 ===
    protected $fillable = [
        'id',
        'name',
        'hostname',
        'ip',
        'os',
        'arch',
        'version',
        'platform',
        'location',
        'expire_time',
        'status',
        'last_seen_at',
        'created_at',
        'updated_at',
    ];

    // === 字段类型转换 ===
    protected $casts = [
        'expire_time' => 'integer',
        'status' => 'integer',
        'last_seen_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // === 模型事件 ===
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now()->timestamp * 1000;
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now()->timestamp * 1000;
            }
        });

        static::updating(function ($model) {
            $model->updated_at = now()->timestamp * 1000;
        });
    }

    // === 关联关系 ===

    /**
     * 一个探针有多条 CPU 指标记录
     */
    public function cpuMetrics()
    {
        return $this->hasMany(CpuMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条内存指标记录
     */
    public function memoryMetrics()
    {
        return $this->hasMany(MemoryMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条磁盘指标记录
     */
    public function diskMetrics()
    {
        return $this->hasMany(DiskMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条磁盘 IO 指标记录
     */
    public function diskIoMetrics()
    {
        return $this->hasMany(DiskIoMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条网络指标记录
     */
    public function networkMetrics()
    {
        return $this->hasMany(NetworkMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条负载指标记录
     */
    public function loadMetrics()
    {
        return $this->hasMany(LoadMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条 GPU 指标记录
     */
    public function gpuMetrics()
    {
        return $this->hasMany(GpuMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条温度指标记录
     */
    public function temperatureMetrics()
    {
        return $this->hasMany(TemperatureMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条主机信息记录
     */
    public function hostMetrics()
    {
        return $this->hasMany(HostMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条监控检测结果
     */
    public function monitorMetrics()
    {
        return $this->hasMany(MonitorMetric::class, 'agent_id');
    }

    /**
     * 一个探针有多条监控统计数据
     */
    public function monitorStats()
    {
        return $this->hasMany(MonitorStats::class, 'agent_id');
    }

    /**
     * 一个探针有多个告警配置
     */
    public function alertConfigs()
    {
        return $this->hasMany(AlertConfig::class, 'agent_id');
    }

    /**
     * 一个探针有多条告警记录
     */
    public function alertRecords()
    {
        return $this->hasMany(AlertRecord::class, 'agent_id');
    }

    /**
     * 一个探针有多条审计结果
     */
    public function auditResults()
    {
        return $this->hasMany(AuditResult::class, 'agent_id');
    }

    // === 辅助方法 ===

    /**
     * 判断探针是否在线
     * 2 分钟内有心跳视为在线
     */
    public function isOnline(): bool
    {
        $twoMinutesAgo = (now()->timestamp - 120) * 1000;
        return $this->last_seen_at >= $twoMinutesAgo;
    }

    /**
     * 判断探针是否已过期
     */
    public function isExpired(): bool
    {
        if (!$this->expire_time) {
            return false;
        }
        return $this->expire_time < now()->timestamp * 1000;
    }

    /**
     * 更新心跳时间
     */
    public function updateHeartbeat(): void
    {
        $this->update([
            'status' => 1,
            'last_seen_at' => now()->timestamp * 1000,
        ]);
    }
}
```

---

## 3. CpuMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CPU 指标模型
 */
class CpuMetric extends Model
{
    protected $table = 'cpu_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'usage_percent',
        'logical_cores',
        'physical_cores',
        'model_name',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'usage_percent' => 'float',
        'logical_cores' => 'integer',
        'physical_cores' => 'integer',
        'timestamp' => 'integer',
    ];

    /**
     * 关联到探针
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
```

---

## 4. MemoryMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 内存指标模型
 */
class MemoryMetric extends Model
{
    protected $table = 'memory_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'total',
        'used',
        'free',
        'usage_percent',
        'swap_total',
        'swap_used',
        'swap_free',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'total' => 'integer',
        'used' => 'integer',
        'free' => 'integer',
        'usage_percent' => 'float',
        'swap_total' => 'integer',
        'swap_used' => 'integer',
        'swap_free' => 'integer',
        'timestamp' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
```

---

## 5. DiskMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 磁盘容量指标模型
 */
class DiskMetric extends Model
{
    protected $table = 'disk_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'mount_point',
        'total',
        'used',
        'free',
        'usage_percent',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'total' => 'integer',
        'used' => 'integer',
        'free' => 'integer',
        'usage_percent' => 'float',
        'timestamp' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
```

---

## 6. DiskIoMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 磁盘 IO 指标模型
 */
class DiskIoMetric extends Model
{
    protected $table = 'disk_io_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'device',
        'read_count',
        'write_count',
        'read_bytes',
        'write_bytes',
        'read_bytes_rate',
        'write_bytes_rate',
        'read_time',
        'write_time',
        'io_time',
        'iops_in_progress',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'read_count' => 'integer',
        'write_count' => 'integer',
        'read_bytes' => 'integer',
        'write_bytes' => 'integer',
        'read_bytes_rate' => 'integer',
        'write_bytes_rate' => 'integer',
        'read_time' => 'integer',
        'write_time' => 'integer',
        'io_time' => 'integer',
        'iops_in_progress' => 'integer',
        'timestamp' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
```

---

## 7. NetworkMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 网络流量指标模型
 */
class NetworkMetric extends Model
{
    protected $table = 'network_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'interface',
        'bytes_sent_rate',
        'bytes_recv_rate',
        'bytes_sent_total',
        'bytes_recv_total',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'bytes_sent_rate' => 'integer',
        'bytes_recv_rate' => 'integer',
        'bytes_sent_total' => 'integer',
        'bytes_recv_total' => 'integer',
        'timestamp' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
```

---

## 8. LoadMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 系统负载指标模型
 */
class LoadMetric extends Model
{
    protected $table = 'load_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'load1',
        'load5',
        'load15',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'load1' => 'float',
        'load5' => 'float',
        'load15' => 'float',
        'timestamp' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
```

---

## 9. GpuMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * GPU 性能指标模型
 */
class GpuMetric extends Model
{
    protected $table = 'gpu_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'index',
        'name',
        'utilization',
        'memory_total',
        'memory_used',
        'memory_free',
        'temperature',
        'power_draw',
        'fan_speed',
        'performance_state',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'index' => 'integer',
        'utilization' => 'float',
        'memory_total' => 'integer',
        'memory_used' => 'integer',
        'memory_free' => 'integer',
        'temperature' => 'float',
        'power_draw' => 'float',
        'fan_speed' => 'float',
        'timestamp' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
```

---

## 10. TemperatureMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 温度传感器指标模型
 */
class TemperatureMetric extends Model
{
    protected $table = 'temperature_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'sensor_key',
        'sensor_label',
        'temperature',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'temperature' => 'float',
        'timestamp' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
```

---

## 11. HostMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 主机系统信息模型
 */
class HostMetric extends Model
{
    protected $table = 'host_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'os',
        'platform',
        'platform_version',
        'kernel_version',
        'kernel_arch',
        'uptime',
        'boot_time',
        'procs',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'uptime' => 'integer',
        'boot_time' => 'integer',
        'procs' => 'integer',
        'timestamp' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
```

---

## 12. MonitorMetric 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 服务监控检测结果模型
 */
class MonitorMetric extends Model
{
    protected $table = 'monitor_metrics';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'monitor_id',
        'type',
        'target',
        'status',
        'status_code',
        'response_time',
        'error',
        'message',
        'content_match',
        'cert_expiry_time',
        'cert_days_left',
        'timestamp',
        'created_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'response_time' => 'integer',
        'content_match' => 'boolean',
        'cert_expiry_time' => 'integer',
        'cert_days_left' => 'integer',
        'timestamp' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function monitorTask()
    {
        return $this->belongsTo(MonitorTask::class, 'monitor_id');
    }
}
```

---

## 13. MonitorTask 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * 服务监控任务配置模型
 */
class MonitorTask extends Model
{
    protected $table = 'monitor_tasks';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'type',
        'target',
        'description',
        'enabled',
        'show_target_public',
        'interval',
        'agent_ids',
        'http_config',
        'tcp_config',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'show_target_public' => 'boolean',
        'interval' => 'integer',
        'agent_ids' => 'array', // JSON 数组
        'http_config' => 'array', // JSON 对象
        'tcp_config' => 'array', // JSON 对象
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now()->timestamp * 1000;
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now()->timestamp * 1000;
            }
        });

        static::updating(function ($model) {
            $model->updated_at = now()->timestamp * 1000;
        });
    }

    /**
     * 一个监控任务有多次检测结果
     */
    public function monitorMetrics()
    {
        return $this->hasMany(MonitorMetric::class, 'monitor_id');
    }

    /**
     * 一个监控任务有对应的统计数据
     */
    public function monitorStats()
    {
        return $this->hasMany(MonitorStats::class, 'monitor_id');
    }

    /**
     * 获取分配的探针列表（多对多关系）
     * 通过 agent_ids JSON 字段实现
     */
    public function getAssignedAgents()
    {
        if (!$this->agent_ids || empty($this->agent_ids)) {
            // 如果没有指定探针，返回所有探针
            return Agent::all();
        }

        // 返回指定的探针
        return Agent::whereIn('id', $this->agent_ids)->get();
    }
}
```

---

## 14. MonitorStats 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * 服务监控统计数据模型
 */
class MonitorStats extends Model
{
    protected $table = 'monitor_stats';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'agent_id',
        'monitor_id',
        'monitor_type',
        'target',
        'current_response',
        'avg_response_24h',
        'uptime_24h',
        'uptime_30d',
        'cert_expiry_date',
        'cert_expiry_days',
        'total_checks_24h',
        'success_checks_24h',
        'total_checks_30d',
        'success_checks_30d',
        'last_check_time',
        'last_check_status',
        'updated_at',
    ];

    protected $casts = [
        'current_response' => 'integer',
        'avg_response_24h' => 'integer',
        'uptime_24h' => 'float',
        'uptime_30d' => 'float',
        'cert_expiry_date' => 'integer',
        'cert_expiry_days' => 'integer',
        'total_checks_24h' => 'integer',
        'success_checks_24h' => 'integer',
        'total_checks_30d' => 'integer',
        'success_checks_30d' => 'integer',
        'last_check_time' => 'integer',
        'updated_at' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now()->timestamp * 1000;
            }
        });

        static::updating(function ($model) {
            $model->updated_at = now()->timestamp * 1000;
        });
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function monitorTask()
    {
        return $this->belongsTo(MonitorTask::class, 'monitor_id');
    }
}
```

---

## 15. AlertConfig 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * 告警规则配置模型
 */
class AlertConfig extends Model
{
    protected $table = 'alert_configs';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'agent_id',
        'name',
        'enabled',
        'rule_cpu_enabled',
        'rule_cpu_threshold',
        'rule_cpu_duration',
        'rule_memory_enabled',
        'rule_memory_threshold',
        'rule_memory_duration',
        'rule_disk_enabled',
        'rule_disk_threshold',
        'rule_disk_duration',
        'rule_network_enabled',
        'rule_network_duration',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'rule_cpu_enabled' => 'boolean',
        'rule_cpu_threshold' => 'float',
        'rule_cpu_duration' => 'integer',
        'rule_memory_enabled' => 'boolean',
        'rule_memory_threshold' => 'float',
        'rule_memory_duration' => 'integer',
        'rule_disk_enabled' => 'boolean',
        'rule_disk_threshold' => 'float',
        'rule_disk_duration' => 'integer',
        'rule_network_enabled' => 'boolean',
        'rule_network_duration' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now()->timestamp * 1000;
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now()->timestamp * 1000;
            }
        });

        static::updating(function ($model) {
            $model->updated_at = now()->timestamp * 1000;
        });
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function alertRecords()
    {
        return $this->hasMany(AlertRecord::class, 'config_id');
    }
}
```

---

## 16. AlertRecord 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 告警触发记录模型
 */
class AlertRecord extends Model
{
    protected $table = 'alert_records';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'config_id',
        'config_name',
        'alert_type',
        'message',
        'threshold',
        'actual_value',
        'level',
        'status',
        'fired_at',
        'resolved_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'threshold' => 'float',
        'actual_value' => 'float',
        'fired_at' => 'integer',
        'resolved_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function alertConfig()
    {
        return $this->belongsTo(AlertConfig::class, 'config_id');
    }

    /**
     * 判断告警是否已恢复
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved' && $this->resolved_at !== null;
    }

    /**
     * 标记告警为已恢复
     */
    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now()->timestamp * 1000,
        ]);
    }
}
```

---

## 17. AuditResult 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 系统审计结果模型
 */
class AuditResult extends Model
{
    protected $table = 'audit_results';
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'type',
        'result',
        'start_time',
        'end_time',
        'created_at',
    ];

    protected $casts = [
        'result' => 'array', // JSON 数据
        'start_time' => 'integer',
        'end_time' => 'integer',
        'created_at' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * 获取审计耗时（秒）
     */
    public function getDurationAttribute(): float
    {
        return ($this->end_time - $this->start_time) / 1000;
    }
}
```

---

## 18. Property 模型

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 系统配置属性模型
 */
class Property extends Model
{
    protected $table = 'properties';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'value',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'value' => 'array', // JSON 数据
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->timestamp * 1000;
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now()->timestamp * 1000;
            }
        });

        static::updating(function ($model) {
            $model->updated_at = now()->timestamp * 1000;
        });
    }

    /**
     * 获取配置值（静态方法）
     */
    public static function getValue(string $key, $default = null)
    {
        $property = static::find($key);
        return $property ? $property->value : $default;
    }

    /**
     * 设置配置值（静态方法）
     */
    public static function setValue(string $key, string $name, $value): void
    {
        static::updateOrCreate(
            ['id' => $key],
            [
                'name' => $name,
                'value' => $value,
            ]
        );
    }
}
```

---

## 🎯 模型创建命令

```bash
# 创建所有模型（按顺序执行）
php artisan make:model ApiKey
php artisan make:model Agent
php artisan make:model CpuMetric
php artisan make:model MemoryMetric
php artisan make:model DiskMetric
php artisan make:model DiskIoMetric
php artisan make:model NetworkMetric
php artisan make:model LoadMetric
php artisan make:model GpuMetric
php artisan make:model TemperatureMetric
php artisan make:model HostMetric
php artisan make:model MonitorMetric
php artisan make:model MonitorTask
php artisan make:model MonitorStats
php artisan make:model AlertConfig
php artisan make:model AlertRecord
php artisan make:model AuditResult
php artisan make:model Property
```

---

## 📝 注意事项

1. **时间戳处理**：所有时间戳字段使用毫秒级 BIGINT，需要手动管理
2. **UUID 主键**：部分表使用 UUID 字符串主键，需要在 boot() 方法中自动生成
3. **JSON 字段**：使用 `'array'` cast 自动转换 JSON 数据
4. **关联关系**：主要是一对多关系，只有 agents ↔ monitor_tasks 是多对多（通过 JSON 实现）
5. **外键约束**：数据库已设置外键约束，模型中添加了 belongsTo/hasMany 关系定义

---

**复制这些代码到对应的模型文件中即可！**
