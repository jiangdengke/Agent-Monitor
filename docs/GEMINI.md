# 项目上下文 (GEMINI.md)

## 📂 项目概览

这是一个基于 **Laravel 11** 开发的服务器监控系统后端（Agent Monitor）。该系统接收来自 Go 探针（Agent）的监控数据，进行存储、分析，并提供实时告警和数据展示功能。

**当前关键架构变更**：
尽管部分旧文档（如 `README.md`）可能仍提及 WebSocket/Swoole，但根据最新的 [架构决策](./docs/ARCHITECTURE_DECISION.md) 和 [任务清单](./docs/task.md)，**项目已全面转向 HTTP POST 架构**。

- **Server 端**：Laravel 11 (Standard FPM/Nginx 模式，无需 Swoole)。
- **Agent 通信**：通过 HTTP POST 接口上报数据。
- **实时推送**：仅在 Server -> 前端浏览器之间使用 WebSocket (Laravel Reverb) 进行数据推送。

## 🛠️ 技术栈

- **框架**: Laravel 11 (PHP 8.2+)
- **数据库**: PostgreSQL 14+ (设计上兼容 TimescaleDB 扩展)
- **缓存/队列**: Redis
- **实时广播**: Laravel Reverb
- **API 响应**: `jiannei/laravel-response` (统一响应格式)
- **Agent (客户端)**: Go (计划中，使用 HTTP Client)

## 📍 目录结构重点

| 路径 | 说明 |
|------|------|
| `docs/` | **核心文档源**。包含 `task.md` (进度), `db_schema.md` (库表), `ARCHITECTURE_DECISION.md` (决策)。 |
| `app/Models/` | 数据模型。包含 `Agent`, `ApiKey` 以及各种 `*Metric` (CpuMetric, MemoryMetric...)。 |
| `app/Http/Controllers/Api/` | API 控制器。目前包含 `AgentController` (注册/心跳)。需新增 `MetricController`。 |
| `database/migrations/` | 数据库迁移文件。包含完整的 18 张表结构定义。 |
| `app/Http/Middleware/AuthenticateApiKey.php` | Agent 鉴权中间件。 |

## 🚦 当前开发状态 (截至 2025-11-26)

**已完成 (Phase 1 & 部分 Phase 2):**
1.  **数据库架构**: 所有迁移文件已创建 (`agents`, `metrics`, `alert_configs` 等)。
2.  **基础模型**: Eloquent 模型已建立，包含关联关系。
3.  **认证机制**: `ApiKeyAuth` 中间件已实现。
4.  **Agent 基础 API**:
    *   `POST /api/agent/register`: 探针注册。
    *   `POST /api/agent/heartbeat`: 心跳保活。

**进行中/下一步:**
1.  **指标上报接口**: 需实现 `MetricController` 处理 `POST /api/agent/metrics`。
2.  **Go Agent 开发**: 开发基于 HTTP 的采集探针。
3.  **实时推送**: 集成 Reverb，在接收到 HTTP 指标后广播事件。

## 🚀 常用命令

### 初始化
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# 配置 .env 中的 DB_CONNECTION=pgsql 等信息
php artisan migrate
```

### 运行服务
```bash
# 启动 API 服务
php artisan serve

# 启动 WebSocket 广播服务 (用于前端推送)
php artisan reverb:start

# 启动队列 (用于异步处理告警等)
php artisan queue:work
```

### 测试 API
```bash
# 创建 API Key (Tinker)
php artisan tinker
>>> \App\Models\ApiKey::create(['name'=>'Test','key'=>'YOUR_KEY','enabled'=>true]);
```

## 📝 开发规范

1.  **API 响应**: 必须使用 `Response::success()` 或 `Response::fail()` 返回统一 JSON 格式。
2.  **类型安全**: 尽量使用 PHP 强类型声明。
3.  **架构遵循**: 严格遵守 `docs/task.md` 中的阶段规划。
4.  **注释**: 核心逻辑需保留中文注释，特别是复杂的计算逻辑。

---
*此文件由 Gemini Agent 生成，用于维护项目上下文理解。*
