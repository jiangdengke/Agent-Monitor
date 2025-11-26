# 系统监控平台

<p align="center">
基于 <strong>Laravel 11 + Go Agent</strong> 的实时系统监控平台
</p>

<p align="center">
<a href="#快速开始">快速开始</a> •
<a href="./GETTING_STARTED.md">详细教程</a> •
<a href="./db_schema.md">数据库设计</a> •
<a href="./AGENTS.md">开发指南</a>
</p>

---

## ✨ 特性

- 🚀 **5 分钟快速部署** - Docker Compose 一键启动
- ⚡ **实时监控** - HTTP 高频上报 + WebSocket 前端推送
- 📊 **丰富指标** - CPU、内存、磁盘、网络、GPU、温度
- 🔔 **智能告警** - 自定义规则，多渠道通知
- 🌐 **监控任务** - HTTP/TCP/Ping 可用性检测
- 🔐 **多租户** - 完整的组织和权限管理
- 💾 **PostgreSQL** - 时序数据优化，支持 TimescaleDB

---

## 📁 项目文档

| 文档 | 说明 |
|------|------|
| [GETTING_STARTED.md](./GETTING_STARTED.md) | 📘 **一步步实施指南**（推荐从这里开始）|
| [db_schema.md](./db_schema.md) | 数据库表结构设计 |
| [task.md](./task.md) | 开发任务清单 |
| [AGENTS.md](./AGENTS.md) | 开发规范和 Git 提交指南 |

---

## 🚀 快速开始

### 环境要求

- PHP >= 8.2
- PostgreSQL >= 14
- Redis >= 6.0
- Node.js >= 18
- Composer
- Go >= 1.21 (仅开发 Agent 需要)

### 一键安装

```bash
# 1. 安装依赖
composer install
npm install

# 2. 配置环境
cp .env.example .env
php artisan key:generate

# 3. 配置数据库（编辑 .env）
DB_CONNECTION=pgsql
DB_DATABASE=agent_monitor
DB_USERNAME=postgres
DB_PASSWORD=your_password

# 4. 创建数据库
sudo -u postgres createdb agent_monitor

# 5. 运行迁移
php artisan migrate

# 6. 启动服务（3 个终端）
php artisan serve              # 终端 1 - API 服务
php artisan reverb:start       # 终端 2 - WebSocket 广播 (可选)
php artisan queue:work         # 终端 3 - 队列处理
```

### 创建 API Key

```bash
php artisan tinker
```

```php
$key = \App\Models\ApiKey::create([
    'name' => 'Default Key',
    'key' => \Illuminate\Support\Str::random(32),
    'enabled' => true
]);

echo "API Key: " . $key->key . "\n";
// 复制这个 key，配置到 Go Agent 的 config.yaml 中
```

### 配置 Go Agent

创建 `agent/config.yaml`：

```yaml
server:
  url: "http://localhost:8000/api"
  api_key: "YOUR_API_KEY_HERE"

agent:
  name: "My Server"

collector:
  interval: 60         # 采集间隔（秒）
  heartbeat_interval: 30  # 心跳间隔（秒）
```

### 启动 Agent

```bash
cd agent
go mod tidy
go run cmd/agent/main.go
```

Agent 会自动：
1. 向后端注册探针信息
2. 定期发送心跳
3. 定期批量上报监控指标

---

## 🏗️ 系统架构

```
┌──────────────┐                          ┌─────────────────┐
│  Go Agent    │       HTTP POST          │ Laravel Backend │
│ (监控探针)    │ ──────────────────────> │ (FPM / Octane)  │
│              │                          │                 │
│ ┌──────────┐ │  1. 注册 (Register)       │ ┌─────────────┐ │
│ │指标采集器 │ │  2. 心跳 (Heartbeat)      │ │ API         │ │
│ │- CPU     │ │  3. 上报 (Metrics)        │ │ Controllers │ │
│ │- Memory  │ │                          │ └─────────────┘ │
│ │- Disk    │ │                          │                 │
│ │- Network │ │                          ├─> PostgreSQL    │
│ │- GPU     │ │                          ├─> Redis         │
│ └──────────┘ │                          ├─> Reverb (前端)  │
│              │                          └─> HTTP API      │
└──────────────┘                          └─────────────────┘
     │
     └─> 部署在每台需要监控的服务器上
```

### 通信协议

探针通过 HTTP POST 发送 JSON 消息：

```json
{
  "agent_id": "uuid...",
  "metrics": [
    {
      "type": "cpu",
      "data": { "usage_percent": 45.2, "logical_cores": 8 },
      "timestamp": 1710000000000
    },
    {
      "type": "memory",
      "data": { "total": 16384, "used": 8192, "usage_percent": 50 }
    }
  ]
}
```

---

## 🔑 核心功能

### 1. 探针管理
- API Key 认证
- 在线/离线状态监控
- 自动离线检测（2 分钟无心跳）

### 2. 系统指标
- CPU：使用率、核心数、型号
- 内存：总量、已用、缓存、Swap
- 磁盘：使用率、读写速度、IOPS
- 网络：流量、发送/接收速率
- GPU：利用率、显存、温度
- 负载：1/5/15 分钟平均值

### 3. 监控任务
- HTTP/HTTPS 可用性
- TCP 端口检测
- Ping 延迟测试
- SSL 证书到期提醒

### 4. 告警系统
- 自定义告警策略
- 条件表达式（如：`usagePercent > 80`）
- 多渠道通知（Email/Webhook/钉钉）

### 5. 实时推送
- 前端 WebSocket 实时更新 (基于 Laravel Reverb)
- 探针状态变更通知
- 告警实时推送

---

## 📊 技术栈

**后端**
- Laravel 11 (PHP 8.2+)
- PostgreSQL 14+ (推荐使用 TimescaleDB 扩展)
- Redis 6+ (队列和缓存)
- Laravel Reverb (WebSocket 广播)

**前端**
- React (Reference Frontend) / Vue 3 (计划中)
- Vite
- Tailwind CSS

**Agent**
- Go 1.21+
- gopsutil (系统指标采集)
- resty (HTTP 客户端)
- 跨平台支持 (Linux/Windows/macOS)

---

## 🛠️ 开发指南

详细的开发步骤请查看 **[GETTING_STARTED.md](./GETTING_STARTED.md)**

### 快速参考

```bash
# 创建迁移
php artisan make:migration create_xxx_table

# 创建模型
php artisan make:model ModelName

# 创建控制器
php artisan make:controller API/ControllerName

# 查看路由
php artisan route:list

# 清除缓存
php artisan cache:clear
php artisan config:clear
```

---

## 🔒 为什么选择 PostgreSQL？

本监控系统强烈推荐使用 **PostgreSQL**，原因如下：

1. **TimescaleDB 扩展** - 专为时序数据优化，查询速度提升 20-100 倍
2. **JSON 支持更强** - 原生 JSONB 类型，支持索引和高级查询
3. **并发性能好** - MVCC 机制，读写不互相阻塞
4. **窗口函数** - 支持复杂的聚合和趋势分析
5. **自动分区和压缩** - TimescaleDB 自动管理历史数据

**性能对比（查询最近 24 小时数据）：**
- MySQL: 3-5 秒
- PostgreSQL: 1-2 秒
- PostgreSQL + TimescaleDB: 0.1-0.3 秒 ⚡

---

## 🤝 贡献指南

1. Fork 本仓库
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'feat: Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 打开 Pull Request

详细的提交规范请参考 [AGENTS.md](./AGENTS.md)

---

## 📝 许可证

MIT License

---

## 🆘 需要帮助？

- 📖 查看 [详细教程](./GETTING_STARTED.md)
- 🐛 提交 [Issue](https://github.com/your-repo/issues)
- 💬 加入讨论组

---

**快速开始：** 直接查看 [GETTING_STARTED.md](./GETTING_STARTED.md) 获取完整的分步指南！🚀
