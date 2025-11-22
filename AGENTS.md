# 系统监控平台开发指南

## 项目架构概述
本项目是基于 Laravel + Go Agent 的实时监控系统。Laravel 后端负责数据存储、API 服务、WebSocket 实时推送和告警管理；Go Agent 部署在被监控设备上采集系统指标并通过 WebSocket/HTTP 上报。

## 项目结构
- `app/` - Laravel 应用核心（Controllers, Models, Services, Jobs, Events）
- `routes/` - API 路由定义（`api.php` 用于 RESTful API，`channels.php` 用于 WebSocket）
- `database/migrations/` - 数据库迁移文件
- `database/seeders/` - 初始数据填充
- `resources/` - 前端资源（Views, JS, CSS）
- `tests/` - 测试文件（Feature 和 Unit 测试）
- `config/` - 配置文件
- `storage/` - 日志、缓存、文件存储

## 开发环境搭建

### 1. 基础依赖安装
```bash
# 安装 PHP 依赖
composer install

# 安装前端依赖
npm install

# 配置环境变量
cp .env.example .env
php artisan key:generate
```

### 2. 数据库配置
配置 `.env` 中的数据库连接（推荐使用 PostgreSQL 或 MySQL）：
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=agent_monitor
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. WebSocket 配置
```bash
# 安装 Laravel Reverb
composer require laravel/reverb
php artisan reverb:install
```

配置 `.env`：
```env
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=redis
```

### 4. 数据库迁移
```bash
php artisan migrate --seed
```

### 5. 启动服务
```bash
# 启动 Laravel 服务器
php artisan serve

# 启动 WebSocket 服务器（新终端）
php artisan reverb:start

# 启动队列处理（新终端）
php artisan queue:work

# 启动前端开发服务器（新终端）
npm run dev
```

### 6. 测试
```bash
# 运行所有测试
php artisan test

# 运行特定测试
php artisan test --filter AgentRegistrationTest
```

## Go Agent 集成

### Agent 通信协议
Go Agent 通过以下方式与 Laravel 后端通信：

1. **注册** - `POST /api/agent/register`
   - 使用 API Key 认证（Header: `X-API-Key`）
   - 上报探针信息（ID、名称、主机名、OS、架构等）
   - 返回注册状态和配置

2. **心跳** - `POST /api/agent/heartbeat`
   - 定期上报在线状态（建议 30-60 秒）
   - 接收服务端配置更新（监控任务等）

3. **指标上报** - `POST /api/agent/metrics`
   - 批量上报系统指标（CPU、内存、磁盘、网络等）
   - 支持 JSON 格式的指标数据

4. **监控结果** - `POST /api/agent/monitor-results`
   - 上报 HTTP/TCP/Ping 监控任务结果

### Agent 配置示例
修改 Go Agent 的配置文件：
```yaml
server_url: "http://localhost:8000"
api_key: "your-laravel-api-key"
heartbeat_interval: 30
metrics_interval: 60
```

### Agent 修改指南
1. 修改 `pkg/agent/config/config.go` 添加 Laravel 服务器配置
2. 修改 `pkg/agent/service/client.go` 实现 Laravel API 调用
3. 在请求中添加 `X-API-Key` 头部认证

## API 认证机制

### API Key 认证（Agent 使用）
- 在 Laravel 中生成 API Key
- Agent 在每个请求中携带 `X-API-Key` 头部
- 后端使用 `ApiKeyAuth` 中间件验证

### Sanctum 认证（Web 管理端使用）
- 用户登录后获取 Token
- 管理端 API 使用 `auth:sanctum` 中间件保护

## WebSocket 实时推送

### 前端监听示例
```javascript
Echo.channel('organization.{orgId}.metrics')
    .listen('MetricsReceived', (e) => {
        console.log('实时指标:', e.metrics);
    });
```

### 后端广播示例
```php
broadcast(new MetricsReceived($agent, $metrics));
```

## 编码规范

### PHP 代码规范
- 遵循 PSR-12 规范
- 使用 Laravel Pint 格式化代码：`./vendor/bin/pint`
- 控制器保持精简，业务逻辑放在 Service 层
- 类名使用 StudlyCase，方法使用 camelCase
- 数据库字段使用 snake_case

### 数据库迁移规范
- 迁移文件命名：`YYYY_MM_DD_HHMMSS_create_table_name.php`
- 总是提供 `up()` 和 `down()` 方法
- 添加必要的索引和外键约束

### 测试规范
- 每个新功能必须编写测试
- 测试文件命名：`*Test.php`
- 使用 Factory 生成测试数据
- 测试覆盖正常流程和异常情况

## Git 提交规范

### Commit 格式
```
类型(范围): 简短描述

详细描述（可选）

相关 Issue: #123
```

类型包括：
- `feat`: 新功能
- `fix`: 修复 Bug
- `docs`: 文档更新
- `style`: 代码格式调整
- `refactor`: 重构
- `test`: 添加测试
- `chore`: 构建/工具变更

### Pull Request 规范
- 描述变更动机和实现方案
- 列出数据库迁移和破坏性变更
- 说明测试方法
- 附上 API 示例或截图

## 安全建议

- **不要提交** `.env` 文件和敏感信息
- API Key 必须随机生成且足够长（32+ 字符）
- 使用 HTTPS 保护生产环境通信
- 定期更新依赖包：`composer update`
- 数据库密码使用强密码
- 启用 CORS 保护：配置 `config/cors.php`

## 性能优化

### 数据库优化
- 为常用查询字段添加索引
- 使用 Eager Loading 避免 N+1 查询
- 考虑使用 TimescaleDB 存储时序数据
- 定期清理过期数据

### 缓存策略
- 使用 Redis 缓存探针状态和最新指标
- 缓存热点数据（如在线探针列表）
- 设置合理的缓存过期时间

### 队列优化
- 告警检查必须异步处理
- 通知发送使用队列
- 配置多个队列 Worker 处理高并发

## 故障排查

### 常用命令
```bash
# 查看实时日志
php artisan pail

# 查看队列任务
php artisan queue:work --tries=1

# 清除缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 查看路由列表
php artisan route:list

# 数据库调试
php artisan tinker
```

### 调试技巧
```php
// 启用 SQL 查询日志
\DB::enableQueryLog();
// ... 执行查询
dd(\DB::getQueryLog());

// 查看变量
dd($variable);
dump($variable);
```
