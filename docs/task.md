# 开发任务清单

> 这是一个精简的任务清单，详细的实施步骤请查看 [GETTING_STARTED.md](./GETTING_STARTED.md)

## 🏗️ 架构说明

**重要**：本项目采用 **HTTP 短连接** 架构，而非 WebSocket 长连接：

- **Server 端**：Laravel 11 (Standard)
- **Agent 端**：Go 编写的轻量级探针（HTTP 客户端）
- **通信方式**：探针通过 HTTP POST 请求发送指标数据
- **协议格式**：JSON 消息

```
Agent (Go) --HTTP POST--> Laravel --> PostgreSQL
```

---

## 🎯 里程碑

- [x] **M1**：探针可以注册和发送心跳 ✅
- [x] **M2**：指标数据正常上报和存储 ✅
- [ ] **M3**：前端实时更新工作正常
- [x] **M4**：监控任务配置下发并执行 ✅
- [x] **M5**：告警系统正常触发和通知 ✅
- [x] **MVP**：基本功能可用，可对接真实 Agent ✅

---

## 第 1 阶段：基础架构 ✅ 完成

### 环境准备
- [x] 安装 PHP 8.2+
- [x] 安装 PostgreSQL 14+
- [x] 安装 Redis 6+
- [x] `composer install`

### 数据库
- [x] 配置 `.env` 文件（PostgreSQL 连接）
- [x] 创建数据库 `agent_monitor`
- [x] 所有 Migrations 创建完成
- [x] 执行 `php artisan migrate`

### 模型
- [x] `ApiKey` 模型 + `isValid()` 方法
- [x] `Agent` 模型 + `isOnline()` 方法
- [x] 各种 Metric 模型（CPU, Memory, Disk 等）
- [x] `AlertConfig` 模型
- [x] `AlertRecord` 模型

### 认证
- [x] `ApiKeyAuth` 中间件
- [x] 注册中间件到 `bootstrap/app.php`

---

## 第 2 阶段：Agent API (HTTP) ✅ 完成

### 控制器
- [x] `AgentController::register()`
- [x] `AgentController::heartbeat()`
- [x] `MetricController::store()` - 批量上报
- [x] `MetricController::index()` - 查询指标

### 路由
- [x] `POST /api/agents/register`
- [x] `POST /api/agents/{id}/heartbeat`
- [x] `POST /api/agents/metrics` - 批量上报

### 测试
- [x] 使用 Go Agent 测试注册
- [x] 使用 Go Agent 测试心跳
- [x] 使用 Go Agent 测试指标上报
- [x] 验证数据库中有 Agent 和 Metric 记录

---

## 第 3 阶段：告警系统 ✅ 完成

### 实现
- [x] `AlertConfig` 模型 - 告警配置
- [x] `AlertRecord` 模型 - 告警记录
- [x] `CheckAlerts` Job - 异步告警检查
- [x] `AlertService` - 告警逻辑服务
  - [x] 检查 CPU/内存/磁盘阈值
  - [x] 持续时间判断 (duration)
  - [x] 使用 Cache 保存状态
  - [x] 触发告警 `fireAlert()`
  - [x] 恢复告警 `resolveAlert()`
  - [x] 日志记录

### 待完成
- [ ] 通知发送（邮件/Webhook）

---

## 第 4 阶段：Go Agent ✅ 完成

### 项目结构
- [x] 创建 Go 项目目录结构 (`backend/agent/`)
- [x] 初始化 `go.mod`
- [x] 定义配置文件格式 (`config.yaml`)

### HTTP 客户端
- [x] 实现 HTTP Client (Resty)
- [x] 实现注册逻辑
- [x] 实现心跳机制
- [x] 实现批量指标上报
- [x] Agent ID 本地持久化

### 指标采集器
- [x] CPU 采集器（使用 gopsutil）
- [x] 内存采集器
- [x] 磁盘采集器
- [x] 磁盘 IO 采集器（含读写速率）
- [x] 网络采集器（含发送/接收速率）
- [x] 系统负载采集器
- [x] 主机信息采集器
- [x] GPU 采集器
- [x] 温度采集器

### 监控任务执行
- [x] HTTP 监控（状态码、响应时间、SSL 证书）
- [x] TCP 监控（端口连通性、响应时间）

### 定时上报
- [x] 定时采集并发送指标（可配置间隔）
- [x] 消息序列化（JSON）
- [x] 优雅退出

### 系统服务
- [x] 支持安装为 systemd 服务（Linux）
- [x] 支持 Windows 服务
- [x] 命令行工具（install/uninstall/start/stop/restart/status）

---

## 第 5 阶段：前端实时推送 (Reverb) ⏳ 待做

### 安装
- [ ] `composer require laravel/reverb`
- [ ] `php artisan reverb:install`
- [ ] 配置 `.env` 中的 `BROADCAST_CONNECTION=reverb`

### 事件
- [ ] `MetricsReceived` 事件（向前端推送）
- [ ] 实现 `ShouldBroadcast`
- [ ] 定义 `broadcastOn()` 和 `broadcastWith()`

### 集成
- [ ] 在 `MetricController` 接收指标后触发事件

### 测试
- [ ] 启动 `php artisan reverb:start --debug`
- [ ] 验证 Reverb 日志中有广播消息
- [ ] 使用浏览器控制台监听事件

---

## 第 6 阶段：监控任务（HTTP/TCP）✅ 完成

- [x] `MonitorTask` CRUD API
- [x] Agent 获取任务列表 API (`GET /api/agents/{agentId}/monitors`)
- [x] Agent 执行 HTTP/TCP 监控
- [x] Agent 上报监控结果 API (`POST /api/monitor-metrics`)
- [x] 监控统计数据 API

---

## 第 7 阶段：后端管理 API ✅ 完成

### 代码重构
- [x] Controller → Service 分层重构
- [x] MetricService（指标存储和查询）
- [x] AgentService（探针管理）
- [x] AlertService（告警 CRUD）

### API Key 管理
- [x] API Key CRUD API
- [x] 启用/禁用 API Key
- [x] 重新生成 API Key

### 指标查询
- [x] 历史指标查询 API
- [x] 最新指标查询 API

### 属性配置
- [x] 属性配置 CRUD API
- [x] 批量配置 API

---

## 第 8 阶段：前端界面（可选）⏳ 待做

- [ ] 探针列表页面
- [ ] 探针详情页面
- [ ] 实时指标图表（CPU/内存/磁盘）
- [ ] WebSocket 实时更新
- [ ] 监控任务管理
- [ ] 告警策略管理

---

## 第 9 阶段：性能优化 ⏳ 待做

- [ ] 安装 TimescaleDB 扩展（PostgreSQL）
- [ ] 将 `metrics` 表转为 Hypertable
- [ ] 添加数据库索引
- [ ] Redis 缓存在线探针列表
- [ ] 缓存最新指标数据
- [ ] 数据归档策略

---

## 第 10 阶段：部署准备 ⏳ 待做

- [ ] 编写 `Dockerfile`
- [ ] 编写 `docker-compose.yml`
- [ ] 配置 Nginx 反向代理
- [ ] 配置 Supervisor（队列守护）
- [ ] 环境变量管理
- [ ] 日志配置
- [ ] 备份脚本

---

## 📝 进度记录

### 2025-11-30
- [x] Agent 添加 GPU、温度采集器
- [x] Agent 添加 HTTP/TCP 监控执行功能
- [x] Agent 添加命令行工具和系统服务支持
- [x] Agent 补充网络速率、磁盘 IO 速率采集
- [x] 后端 Controller → Service 分层重构
- [x] 后端添加 MonitorService/MonitorController
- [x] 后端添加 ApiKeyService/ApiKeyController
- [x] 后端添加 PropertyService/PropertyController
- [x] 后端添加最新指标查询 API

### 2025-11-26
- [x] 完成 HTTP API 架构决策
- [x] 完成 MetricController（批量上报 + 查询）
- [x] 完成 AlertService 告警系统
- [x] 完成 Go Agent 核心功能
- [x] 整理文档到 docs 目录

---

**提示**：详细的实施步骤请查看 [GETTING_STARTED.md](./GETTING_STARTED.md)
