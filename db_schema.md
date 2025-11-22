# 系统监控平台数据库设计（Laravel 版）

> 基于 Go Agent 的数据结构，完全兼容原项目

**设计原则：**
- ✅ 100% 兼容 Go Agent 的数据结构
- ✅ 每种指标一张表（而不是通用 metrics 表）
- ✅ 字段类型、命名完全一致
- ✅ 使用 PostgreSQL + TimescaleDB 优化

---

## 表结构总览

| 分类 | 表名 | 说明 |
|------|------|------|
| **认证** | api_keys | API 密钥 |
| **探针** | agents | 探针信息 |
| **系统指标** | cpu_metrics | CPU 指标 |
| | memory_metrics | 内存指标 |
| | disk_metrics | 磁盘指标 |
| | disk_io_metrics | 磁盘 IO 指标 |
| | network_metrics | 网络指标 |
| | load_metrics | 系统负载 |
| | gpu_metrics | GPU 指标（可选）|
| | temperature_metrics | 温度指标（可选）|
| | host_metrics | 主机信息 |
| **监控** | monitor_tasks | 监控任务 |
| | monitor_metrics | 监控结果 |
| | monitor_stats | 监控统计 |
| **告警** | alert_configs | 告警配置 |
| | alert_records | 告警记录 |
| **审计** | audit_results | 审计结果 |
| **配置** | properties | 通用配置 |

---

## 一、认证

### 1.1 api_keys (API 密钥)

```php
Schema::create('api_keys', function (Blueprint $table) {
    $table->string('id')->primary(); // UUID
    $table->string('name')->index();
    $table->string('key')->unique();
    $table->boolean('enabled')->index()->default(true);
    $table->string('created_by')->index(); // 创建人 ID
    $table->bigInteger('created_at'); // 毫秒时间戳
    $table->bigInteger('updated_at'); // 毫秒时间戳

    $table->index('key');
});
```

---

## 二、探针管理

### 2.1 agents (探针)

```php
Schema::create('agents', function (Blueprint $table) {
    $table->string('id')->primary(); // UUID
    $table->string('name')->index();
    $table->string('hostname')->index()->nullable();
    $table->string('ip')->index()->nullable();
    $table->string('os')->nullable();
    $table->string('arch')->nullable();
    $table->string('version')->nullable();
    $table->string('platform')->nullable();
    $table->string('location')->nullable();
    $table->bigInteger('expire_time')->nullable(); // 到期时间（毫秒）
    $table->integer('status')->default(0); // 0=离线, 1=在线
    $table->bigInteger('last_seen_at')->index(); // 最后上线时间（毫秒）
    $table->bigInteger('created_at'); // 毫秒时间戳
    $table->bigInteger('updated_at'); // 毫秒时间戳

    $table->index('last_seen_at');
});
```

**说明：**
- `id` 使用 UUID 字符串
- `status`：0=离线，1=在线
- 时间戳使用 BIGINT 毫秒

---

## 三、系统指标表（每种指标一张表）

### 3.1 cpu_metrics (CPU 指标)

```php
Schema::create('cpu_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->float('usage_percent'); // CPU 使用率
    $table->integer('logical_cores')->nullable();
    $table->integer('physical_cores')->nullable();
    $table->string('model_name')->nullable();
    $table->bigInteger('timestamp'); // 采集时间（毫秒）
    $table->timestamp('created_at')->nullable(); // Laravel 时间戳

    // 复合索引（重要！）
    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 3.2 memory_metrics (内存指标)

```php
Schema::create('memory_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->bigInteger('total'); // 总内存（字节）
    $table->bigInteger('used'); // 已使用（字节）
    $table->bigInteger('free'); // 空闲（字节）
    $table->float('usage_percent'); // 使用率
    $table->bigInteger('swap_total')->nullable();
    $table->bigInteger('swap_used')->nullable();
    $table->bigInteger('swap_free')->nullable();
    $table->bigInteger('timestamp');
    $table->timestamp('created_at')->nullable();

    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 3.3 disk_metrics (磁盘指标)

```php
Schema::create('disk_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->string('mount_point'); // 挂载点
    $table->bigInteger('total'); // 总容量（字节）
    $table->bigInteger('used'); // 已使用（字节）
    $table->bigInteger('free'); // 空闲（字节）
    $table->float('usage_percent'); // 使用率
    $table->bigInteger('timestamp');
    $table->timestamp('created_at')->nullable();

    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 3.4 disk_io_metrics (磁盘 IO 指标)

```php
Schema::create('disk_io_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->string('device'); // 设备名称
    $table->bigInteger('read_count')->nullable();
    $table->bigInteger('write_count')->nullable();
    $table->bigInteger('read_bytes')->nullable();
    $table->bigInteger('write_bytes')->nullable();
    $table->bigInteger('read_bytes_rate')->nullable(); // 读取速率（字节/秒）
    $table->bigInteger('write_bytes_rate')->nullable(); // 写入速率（字节/秒）
    $table->bigInteger('read_time')->nullable(); // 读取时间（毫秒）
    $table->bigInteger('write_time')->nullable(); // 写入时间（毫秒）
    $table->bigInteger('io_time')->nullable(); // IO 时间（毫秒）
    $table->bigInteger('iops_in_progress')->nullable(); // 正在进行的 IO 操作数
    $table->bigInteger('timestamp');
    $table->timestamp('created_at')->nullable();

    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 3.5 network_metrics (网络指标)

```php
Schema::create('network_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->string('interface'); // 网卡名称
    $table->bigInteger('bytes_sent_rate')->nullable(); // 发送速率（字节/秒）
    $table->bigInteger('bytes_recv_rate')->nullable(); // 接收速率（字节/秒）
    $table->bigInteger('bytes_sent_total')->nullable(); // 累计发送
    $table->bigInteger('bytes_recv_total')->nullable(); // 累计接收
    $table->bigInteger('timestamp');
    $table->timestamp('created_at')->nullable();

    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 3.6 load_metrics (系统负载)

```php
Schema::create('load_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->float('load1'); // 1 分钟负载
    $table->float('load5'); // 5 分钟负载
    $table->float('load15'); // 15 分钟负载
    $table->bigInteger('timestamp');
    $table->timestamp('created_at')->nullable();

    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 3.7 gpu_metrics (GPU 指标 - 可选)

```php
Schema::create('gpu_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->integer('index'); // GPU 索引
    $table->string('name')->nullable(); // GPU 名称
    $table->float('utilization')->nullable(); // GPU 使用率（%）
    $table->bigInteger('memory_total')->nullable(); // 总显存（字节）
    $table->bigInteger('memory_used')->nullable(); // 已使用显存（字节）
    $table->bigInteger('memory_free')->nullable(); // 空闲显存（字节）
    $table->float('temperature')->nullable(); // 温度（℃）
    $table->float('power_draw')->nullable(); // 功耗（瓦）
    $table->float('fan_speed')->nullable(); // 风扇转速（%）
    $table->string('performance_state')->nullable(); // 性能状态
    $table->bigInteger('timestamp');
    $table->timestamp('created_at')->nullable();

    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 3.8 temperature_metrics (温度指标 - 可选)

```php
Schema::create('temperature_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->string('sensor_key'); // 传感器标识
    $table->string('sensor_label')->nullable(); // 传感器标签
    $table->float('temperature'); // 温度（℃）
    $table->bigInteger('timestamp');
    $table->timestamp('created_at')->nullable();

    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 3.9 host_metrics (主机信息)

```php
Schema::create('host_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->string('os')->nullable();
    $table->string('platform')->nullable();
    $table->string('platform_version')->nullable();
    $table->string('kernel_version')->nullable();
    $table->string('kernel_arch')->nullable();
    $table->bigInteger('uptime')->nullable(); // 运行时间（秒）
    $table->bigInteger('boot_time')->nullable(); // 启动时间（Unix 秒）
    $table->bigInteger('procs')->nullable(); // 进程数
    $table->bigInteger('timestamp');
    $table->timestamp('created_at')->nullable();

    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

---

## 四、监控任务

### 4.1 monitor_tasks (监控任务)

```php
Schema::create('monitor_tasks', function (Blueprint $table) {
    $table->string('id')->primary(); // UUID

    $table->string('name')->unique();
    $table->string('type')->index(); // http, tcp, ping
    $table->string('target', 500); // 目标地址
    $table->text('description')->nullable();
    $table->boolean('enabled')->default(true);
    $table->boolean('show_target_public')->default(false); // 公开页面是否显示目标
    $table->integer('interval')->default(60); // 检测频率（秒）
    $table->json('agent_ids')->nullable(); // 指定的探针 ID 列表
    $table->json('http_config')->nullable(); // HTTP 监控配置
    $table->json('tcp_config')->nullable(); // TCP 监控配置
    $table->bigInteger('created_at');
    $table->bigInteger('updated_at');

});
```

### 4.2 monitor_metrics (监控结果)

```php
Schema::create('monitor_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id')->index();

    $table->string('monitor_id')->index(); // 监控项 ID
    $table->string('type'); // http, tcp
    $table->string('target', 500);
    $table->string('status'); // up, down
    $table->integer('status_code')->nullable(); // HTTP 状态码
    $table->bigInteger('response_time')->nullable(); // 响应时间（毫秒）
    $table->text('error')->nullable(); // 错误信息
    $table->text('message')->nullable(); // 附加信息
    $table->boolean('content_match')->nullable(); // 内容匹配结果
    $table->bigInteger('cert_expiry_time')->nullable(); // 证书过期时间（毫秒）
    $table->integer('cert_days_left')->nullable(); // 证书剩余天数
    $table->bigInteger('timestamp');
    $table->timestamp('created_at')->nullable();

    $table->index(['agent_id', 'timestamp'], 'idx_agent_time');
    $table->index('timestamp', 'idx_time');
    $table->index('status');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 4.3 monitor_stats (监控统计)

```php
Schema::create('monitor_stats', function (Blueprint $table) {
    $table->string('id')->primary(); // UUID

    $table->string('agent_id')->index();
    $table->string('monitor_id')->index(); // 监控项 ID
    $table->string('monitor_type'); // http, tcp
    $table->string('target', 500);
    $table->bigInteger('current_response')->nullable(); // 当前响应时间（毫秒）
    $table->bigInteger('avg_response_24h')->nullable(); // 24小时平均响应时间（毫秒）
    $table->float('uptime_24h')->nullable(); // 24小时在线率（百分比）
    $table->float('uptime_30d')->nullable(); // 30天在线率（百分比）
    $table->bigInteger('cert_expiry_date')->nullable(); // 证书过期时间（毫秒），0 表示无证书
    $table->integer('cert_expiry_days')->nullable(); // 证书剩余天数
    $table->bigInteger('total_checks_24h')->nullable(); // 24小时总检测次数
    $table->bigInteger('success_checks_24h')->nullable(); // 24小时成功次数
    $table->bigInteger('total_checks_30d')->nullable(); // 30天总检测次数
    $table->bigInteger('success_checks_30d')->nullable(); // 30天成功次数
    $table->bigInteger('last_check_time')->nullable(); // 最后检测时间（毫秒）
    $table->string('last_check_status')->nullable(); // up, down
    $table->bigInteger('updated_at'); // 毫秒时间戳

    $table->index(['agent_id', 'monitor_id']);

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

---

## 五、告警系统

### 5.1 alert_configs (告警配置)

```php
Schema::create('alert_configs', function (Blueprint $table) {
    $table->string('id')->primary(); // UUID

    $table->string('agent_id')->nullable(); // NULL 表示全局配置
    $table->string('name');
    $table->boolean('enabled')->default(true);

    // CPU 告警规则
    $table->boolean('rule_cpu_enabled')->default(false);
    $table->float('rule_cpu_threshold')->nullable();
    $table->integer('rule_cpu_duration')->nullable(); // 持续时间（秒）

    // 内存告警规则
    $table->boolean('rule_memory_enabled')->default(false);
    $table->float('rule_memory_threshold')->nullable();
    $table->integer('rule_memory_duration')->nullable();

    // 磁盘告警规则
    $table->boolean('rule_disk_enabled')->default(false);
    $table->float('rule_disk_threshold')->nullable();
    $table->integer('rule_disk_duration')->nullable();

    // 网络断开告警规则
    $table->boolean('rule_network_enabled')->default(false);
    $table->integer('rule_network_duration')->nullable();

    $table->bigInteger('created_at');
    $table->bigInteger('updated_at');

    $table->index('agent_id');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

### 5.2 alert_records (告警记录)

```php
Schema::create('alert_records', function (Blueprint $table) {
    $table->id();

    $table->string('agent_id')->index();
    $table->string('config_id')->index(); // 告警配置 ID
    $table->string('config_name');
    $table->string('alert_type'); // cpu, memory, disk, network
    $table->text('message');
    $table->float('threshold')->nullable();
    $table->float('actual_value')->nullable();
    $table->string('level')->default('warning'); // info, warning, critical
    $table->string('status')->default('firing'); // firing, resolved
    $table->bigInteger('fired_at')->index(); // 触发时间（毫秒）
    $table->bigInteger('resolved_at')->nullable(); // 恢复时间（毫秒）
    $table->bigInteger('created_at');
    $table->bigInteger('updated_at');


    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

---

## 六、审计系统

### 6.1 audit_results (审计结果)

```php
Schema::create('audit_results', function (Blueprint $table) {
    $table->id();

    $table->string('agent_id', 64)->index();
    $table->string('type', 32); // vps_audit
    $table->text('result'); // JSON 格式的审计结果
    $table->bigInteger('start_time'); // 开始时间（毫秒）
    $table->bigInteger('end_time'); // 结束时间（毫秒）
    $table->bigInteger('created_at'); // 创建时间（毫秒）

    $table->index('created_at');

    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
});
```

**说明：**
- 用于存储 VPS 安全审计结果
- `result` 字段存储 JSON 格式的详细审计数据

---

## 七、通用配置

### 7.1 properties (通用配置)

```php
Schema::create('properties', function (Blueprint $table) {
    $table->string('id')->primary(); // 配置 ID，如：notification_channels
    $table->string('name'); // 可读名称
    $table->text('value'); // JSON 配置
    $table->bigInteger('created_at'); // 毫秒时间戳
    $table->bigInteger('updated_at'); // 毫秒时间戳

});
```

**说明：**
- 存储通用配置，如通知渠道配置
- 支持的通知类型：
  - `dingtalk`：钉钉（需要 secretKey, signSecret）
  - `wecom`：企业微信（需要 secretKey）
  - `feishu`：飞书（需要 secretKey, signSecret）
  - `webhook`：自定义 Webhook（需要 url）

**配置示例：**
```json
{
  "type": "dingtalk",
  "enabled": true,
  "config": {
    "secretKey": "xxx",
    "signSecret": "xxx"
  }
}
```

---

## 时序数据优化（PostgreSQL + TimescaleDB）

### 创建 Hypertable

如果安装了 TimescaleDB 扩展，可以将指标表转为 Hypertable：

```sql
-- 在 PostgreSQL 中执行（需要先安装 TimescaleDB）

-- CPU 指标
SELECT create_hypertable('cpu_metrics', 'timestamp',
    chunk_time_interval => 86400000, -- 1 天（毫秒）
    migrate_data => true
);

-- 内存指标
SELECT create_hypertable('memory_metrics', 'timestamp',
    chunk_time_interval => 86400000,
    migrate_data => true
);

-- 其他指标表同理...
```

### 自动压缩策略

```sql
-- 7 天前的数据自动压缩
SELECT add_compression_policy('cpu_metrics', INTERVAL '7 days');
SELECT add_compression_policy('memory_metrics', INTERVAL '7 days');
```

### 数据保留策略

```sql
-- 30 天后自动删除
SELECT add_retention_policy('cpu_metrics', INTERVAL '30 days');
SELECT add_retention_policy('memory_metrics', INTERVAL '30 days');
```

---

## 迁移顺序

按以下顺序创建迁移：

1. `create_api_keys_table`
2. `create_agents_table`
3. `create_cpu_metrics_table`
4. `create_memory_metrics_table`
5. `create_disk_metrics_table`
6. `create_disk_io_metrics_table`
7. `create_network_metrics_table`
8. `create_load_metrics_table`
9. `create_gpu_metrics_table` (可选)
10. `create_temperature_metrics_table` (可选)
11. `create_host_metrics_table`
12. `create_monitor_tasks_table`
13. `create_monitor_metrics_table`
14. `create_monitor_stats_table`
15. `create_alert_configs_table`
16. `create_alert_records_table`
17. `create_audit_results_table`
18. `create_properties_table`

---

## 设计特点

| 项目 | Go Agent 原项目 | Laravel 版 |
|------|-----------|-----------|
| 表数量 | 18 个 | 18 个（完全一致）|
| 时间戳 | BIGINT 毫秒 | ✅ BIGINT 毫秒（完全一致）|
| 主键类型 | UUID 字符串 | ✅ UUID 字符串（完全一致）|
| 指标表 | ✅ 多表设计 | ✅ 多表设计（完全一致）|
| 监控统计 | ✅ monitor_stats | ✅ monitor_stats（完全一致）|
| 审计功能 | ✅ audit_results | ✅ audit_results（完全一致）|
| 通用配置 | ✅ properties | ✅ properties（完全一致）|
| 索引策略 | idx_agent_time | ✅ idx_agent_time（完全一致）|
| 外键约束 | ❌ 无 | ✅ 添加外键约束（增强）|

---

## 性能建议

1. **必须添加的索引**（已包含在表设计中）：
   - `(agent_id, timestamp)` - 按探针和时间查询
   - `timestamp` - 按时间范围查询

2. **使用 TimescaleDB**：将所有指标表转为 Hypertable

3. **分区策略**：按天分区（TimescaleDB 自动管理）

4. **压缩策略**：7 天后压缩历史数据

5. **保留策略**：30 天后删除原始数据（可选）

---

## ✅ 总结

### 100% 兼容原项目

本数据库设计与 Go Agent 原项目的所有 18 个表**完全一致**：

**核心表（4 个）：**
- agents, api_keys, monitor_tasks, alert_configs

**指标表（10 个）：**
- cpu_metrics, memory_metrics, disk_metrics, disk_io_metrics, network_metrics, load_metrics, gpu_metrics, temperature_metrics, host_metrics, monitor_metrics

**统计与配置（4 个）：**
- monitor_stats, alert_records, audit_results, properties

### 完全兼容性

- ✅ **表数量**：18 个（与原项目完全一致）
- ✅ **字段类型**：BIGINT 毫秒时间戳、UUID 字符串主键（完全一致）
- ✅ **表结构**：多表设计、字段命名（完全一致）
- ✅ **索引策略**：idx_agent_time 等（完全一致）
- ✅ **数据格式**：JSON 配置、时间戳格式（完全一致）
- ✅ **可以直接对接原 Go Agent，无需修改 Agent 代码**

### 唯一增强

- ✅ 添加了外键约束（原项目无外键）
- ✅ 提升数据完整性，不影响兼容性
