# 开发任务清单

> 这是一个精简的任务清单，详细的实施步骤请查看 [GETTING_STARTED.md](./GETTING_STARTED.md)

---

## 🎯 里程碑

- [ ] **M1**：探针可以注册和发送心跳
- [ ] **M2**：指标数据正常上报和存储
- [ ] **M3**：WebSocket 实时推送工作正常
- [ ] **M4**：监控任务配置下发并执行
- [ ] **M5**：告警系统正常触发和通知
- [ ] **MVP**：基本功能可用，可对接真实 Agent

---

## 第 1 阶段：基础架构 ✅

### 环境准备
- [ ] 安装 PHP 8.2+
- [ ] 安装 PostgreSQL 14+
- [ ] 安装 Redis 6+
- [ ] 安装 Node.js 18+
- [ ] `composer install`
- [ ] `npm install`

### 数据库
- [ ] 配置 `.env` 文件（PostgreSQL 连接）
- [ ] 创建数据库 `agent_monitor`
- [ ] `create_api_keys_table`
- [ ] `create_agents_table`
- [ ] `create_cpu_metrics_table`
- [ ] `create_memory_metrics_table`
- [ ] `create_disk_metrics_table`
- [ ] `create_disk_io_metrics_table`
- [ ] `create_network_metrics_table`
- [ ] `create_load_metrics_table`
- [ ] `create_gpu_metrics_table`
- [ ] `create_temperature_metrics_table`
- [ ] `create_host_metrics_table`
- [ ] `create_monitor_tasks_table`
- [ ] `create_monitor_metrics_table`
- [ ] `create_monitor_stats_table`
- [ ] `create_alert_configs_table`
- [ ] `create_alert_records_table`
- [ ] `create_audit_results_table`
- [ ] `create_properties_table`
- [ ] 执行 `php artisan migrate`

### 模型
- [ ] `ApiKey` 模型 + `isValid()` 方法
- [ ] `Agent` 模型 + `isOnline()` 方法
- [ ] 各种 Metric 模型（CPU, Memory, Disk 等）

### 认证
- [ ] `ApiKeyAuth` 中间件
- [ ] 注册中间件到 `bootstrap/app.php`
- [ ] 创建测试 API Key

---

## 第 2 阶段：Agent API

### 控制器
- [ ] `AgentController::register()`
- [ ] `AgentController::heartbeat()`

### 路由
- [ ] `POST /api/agent/register`
- [ ] `POST /api/agent/heartbeat`

### 测试
- [ ] 使用 cURL 测试注册
- [ ] 使用 cURL 测试心跳
- [ ] 验证数据库中有 Agent 记录

---

## 第 3 阶段：指标采集

### 控制器
- [ ] `MetricController::store()`
- [ ] `MetricController::index()`

### 路由
- [ ] `POST /api/agent/metrics`
- [ ] `GET /api/agents/{agent}/metrics`

### 测试
- [ ] 使用 cURL 上报测试指标
- [ ] 验证数据库中有 Metric 记录
- [ ] 测试指标查询 API

---

## 第 4 阶段：WebSocket 实时推送

### 安装
- [ ] `composer require laravel/reverb`
- [ ] `php artisan reverb:install`
- [ ] 配置 `.env` 中的 `BROADCAST_CONNECTION=reverb`

### 事件
- [ ] `MetricsReceived` 事件
- [ ] 实现 `ShouldBroadcast`
- [ ] 定义 `broadcastOn()` 和 `broadcastWith()`

### 集成
- [ ] 在 `MetricController::store()` 中触发事件

### 测试
- [ ] 启动 `php artisan reverb:start --debug`
- [ ] 上报指标，观察 Reverb 日志
- [ ] 使用浏览器控制台监听事件

---

## 第 5 阶段：监控任务（可选）

- [ ] `create_monitor_tasks_table`
- [ ] `create_monitor_results_table`
- [ ] `MonitorTask` 模型
- [ ] `MonitorResult` 模型
- [ ] `MonitorController` CRUD
- [ ] 在心跳中下发任务配置
- [ ] Agent 上报监控结果

---

## 第 6 阶段：告警系统（可选）

- [ ] `create_alert_policies_table`
- [ ] `create_alert_incidents_table`
- [ ] `create_alert_notifications_table`
- [ ] `AlertPolicy` 模型
- [ ] `AlertIncident` 模型
- [ ] `CheckAlerts` Job（异步评估）
- [ ] `SendAlertNotification` Job
- [ ] 条件表达式解析器
- [ ] 邮件/Webhook 通知

---

## 第 7 阶段：Go Agent 对接

- [ ] 配置 Go Agent 的 `config.yaml`
- [ ] 修改 Agent 代码调用 Laravel API（可选）
- [ ] 测试 Agent 注册
- [ ] 测试 Agent 心跳
- [ ] 测试 Agent 指标上报
- [ ] 验证 Laravel 收到数据

---

## 第 8 阶段：前端界面（可选）

- [ ] 探针列表页面
- [ ] 探针详情页面
- [ ] 实时指标图表（CPU/内存/磁盘）
- [ ] WebSocket 实时更新
- [ ] 监控任务管理
- [ ] 告警策略管理

---

## 第 9 阶段：性能优化

- [ ] 安装 TimescaleDB 扩展（PostgreSQL）
- [ ] 将 `metrics` 表转为 Hypertable
- [ ] 添加数据库索引
- [ ] Redis 缓存在线探针列表
- [ ] 缓存最新指标数据
- [ ] 配置队列 Worker
- [ ] 数据归档策略

---

## 第 10 阶段：部署准备

- [ ] 编写 `Dockerfile`
- [ ] 编写 `docker-compose.yml`
- [ ] 配置 Nginx 反向代理
- [ ] 配置 Supervisor（队列守护）
- [ ] 环境变量管理
- [ ] 日志配置
- [ ] 备份脚本

---

## 📝 每日进度记录

### 2025-11-22
- [x] 创建项目文档
- [ ]

### 2025-11-23
- [ ]

---

**提示**：详细的实施步骤请查看 [GETTING_STARTED.md](./GETTING_STARTED.md)
