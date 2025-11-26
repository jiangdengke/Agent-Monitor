# 系统监控平台 - 快速开始指南

> 本文档是简化版本，只包含必需的步骤，去除了多租户相关内容。

---

## 第 1 天：环境搭建 + 数据库

### 步骤 1：安装依赖
```bash
cd backend
composer install
npm install
```

### 步骤 2：配置环境
```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env` 文件：
```env
APP_NAME="Agent Monitor"
APP_ENV=local
APP_DEBUG=true

# PostgreSQL 数据库
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=agent_monitor
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 队列和缓存使用 Redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# WebSocket
BROADCAST_CONNECTION=reverb
```

### 步骤 3：创建数据库
```bash
sudo -u postgres createdb agent_monitor
```

### 步骤 4：创建迁移文件（18 个表）

**按顺序创建：**

```bash
# 1-2. 核心表
php artisan make:migration create_api_keys_table
php artisan make:migration create_agents_table

# 3-12. 系统指标表
php artisan make:migration create_cpu_metrics_table
php artisan make:migration create_memory_metrics_table
php artisan make:migration create_disk_metrics_table
php artisan make:migration create_disk_io_metrics_table
php artisan make:migration create_network_metrics_table
php artisan make:migration create_load_metrics_table
php artisan make:migration create_gpu_metrics_table
php artisan make:migration create_temperature_metrics_table
php artisan make:migration create_host_metrics_table
php artisan make:migration create_monitor_metrics_table

# 13-14. 监控功能
php artisan make:migration create_monitor_tasks_table
php artisan make:migration create_monitor_stats_table

# 15-16. 告警功能
php artisan make:migration create_alert_configs_table
php artisan make:migration create_alert_records_table

# 17-18. 其他
php artisan make:migration create_audit_results_table
php artisan make:migration create_properties_table
```

### 步骤 5：填写迁移文件内容

参考 `db_schema.md` 中的表结构，复制粘贴到对应的迁移文件中。

**示例 - api_keys 表：**
```php
public function up(): void
{
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
}
```

### 步骤 6：运行迁移
```bash
php artisan migrate
```

---

## 第 2 天：创建模型

### ApiKey 模型
```bash
php artisan make:model ApiKey
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id', 'name', 'key', 'enabled', 'created_by', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function isValid(): bool
    {
        return $this->enabled;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now()->timestamp * 1000;
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now()->timestamp * 1000;
            }
        });
    }
}
```

### Agent 模型
```bash
php artisan make:model Agent
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id', 'name', 'hostname', 'ip', 'os', 'arch', 'version',
        'platform', 'location', 'expire_time', 'status', 'last_seen_at'
    ];

    protected $casts = [
        'status' => 'integer',
        'expire_time' => 'integer',
        'last_seen_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function isOnline(): bool
    {
        // 2 分钟内有心跳视为在线
        $twoMinutesAgo = (now()->timestamp - 120) * 1000;
        return $this->last_seen_at >= $twoMinutesAgo;
    }
}
```

---

## 第 3 天：API 认证中间件

### 创建中间件
```bash
php artisan make:middleware ApiKeyAuth
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json(['error' => 'API Key is required'], 401);
        }

        $key = ApiKey::where('key', $apiKey)->first();

        if (!$key || !$key->isValid()) {
            return response()->json(['error' => 'Invalid API Key'], 401);
        }

        $request->merge(['api_key' => $key]);

        return $next($request);
    }
}
```

### 注册中间件

编辑 `bootstrap/app.php`：
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
    ]);
})
```

### 创建测试 API Key
```bash
php artisan tinker
```

```php
use App\Models\ApiKey;

$key = ApiKey::create([
    'id' => \Illuminate\Support\Str::uuid(),
    'name' => 'Test Key',
    'key' => \Illuminate\Support\Str::random(32),
    'enabled' => true,
    'created_by' => 'system',
    'created_at' => now()->timestamp * 1000,
    'updated_at' => now()->timestamp * 1000,
]);

echo "API Key: " . $key->key . "\n";
// 复制这个 key，后面测试要用
```

---

## 第 4 天：Agent API

### 创建控制器
```bash
php artisan make:controller API/AgentController
```

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    // Agent 注册
    public function register(Request $request)
    {
        $data = $request->validate([
            'agentInfo.id' => 'required|string',
            'agentInfo.name' => 'required|string',
            'agentInfo.hostname' => 'nullable|string',
            'agentInfo.os' => 'nullable|string',
            'agentInfo.arch' => 'nullable|string',
            'agentInfo.version' => 'nullable|string',
        ]);

        $agentInfo = $data['agentInfo'];

        $agent = Agent::updateOrCreate(
            ['id' => $agentInfo['id']],
            [
                'name' => $agentInfo['name'],
                'hostname' => $agentInfo['hostname'] ?? null,
                'ip' => $request->ip(),
                'os' => $agentInfo['os'] ?? null,
                'arch' => $agentInfo['arch'] ?? null,
                'version' => $agentInfo['version'] ?? null,
                'status' => 1, // 在线
                'last_seen_at' => now()->timestamp * 1000,
                'created_at' => now()->timestamp * 1000,
                'updated_at' => now()->timestamp * 1000,
            ]
        );

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $agent
        ]);
    }

    // 心跳
    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
        ]);

        $agent = Agent::find($data['id']);

        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $agent->update([
            'status' => 1,
            'last_seen_at' => now()->timestamp * 1000,
            'updated_at' => now()->timestamp * 1000,
        ]);

        return response()->json([
            'code' => 0,
            'message' => 'success'
        ]);
    }
}
```

### 创建路由

编辑 `routes/api.php`：
```php
use App\Http\Controllers\API\AgentController;

Route::prefix('agent')->middleware('api.key')->group(function () {
    Route::post('/register', [AgentController::class, 'register']);
    Route::post('/heartbeat', [AgentController::class, 'heartbeat']);
});
```

### 测试 API

```bash
# 测试注册
curl -X POST http://localhost:8000/api/agent/register \
  -H "X-API-Key: YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "agentInfo": {
      "id": "test-agent-001",
      "name": "Test Server",
      "hostname": "test-server",
      "os": "linux",
      "arch": "amd64",
      "version": "1.0.0"
    }
  }'

# 测试心跳
curl -X POST http://localhost:8000/api/agent/heartbeat \
  -H "X-API-Key: YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"id": "test-agent-001"}'
```

---

## 第 5 天：指标上报 API

### 创建 MetricController
```bash
php artisan make:controller API/MetricController
```

### 创建路由
```php
Route::prefix('agent')->middleware('api.key')->group(function () {
    Route::post('/register', [AgentController::class, 'register']);
    Route::post('/heartbeat', [AgentController::class, 'heartbeat']);
    Route::post('/metrics', [MetricController::class, 'store']); // 新增
});
```

---

## 第 6 天：WebSocket 实时推送

### 安装 Reverb
```bash
composer require laravel/reverb
php artisan reverb:install
```

### 配置 .env
```env
BROADCAST_CONNECTION=reverb
```

### 启动 Reverb
```bash
php artisan reverb:start --debug
```

---

## 第 7 天：对接 Go Agent

### 配置 Go Agent
在 Go Agent 项目中创建 `config.yaml`：
```yaml
server_url: "http://localhost:8000"
api_key: "YOUR_API_KEY_HERE"
heartbeat_interval: 30
metrics_interval: 60
```

### 运行 Go Agent
```bash
cd <your-agent-directory>
go run cmd/agent/main.go --config=config.yaml
```

### 验证
检查 Laravel 日志和数据库，确认：
- ✅ Agent 注册成功
- ✅ 心跳正常
- ✅ 指标数据正常存储

---

## 🎉 完成！

现在你的监控系统已经可以：
- ✅ Agent 注册和认证
- ✅ 接收指标数据
- ✅ 实时推送（WebSocket）
- ✅ 完全兼容 Go Agent

---

## 📚 参考文档

- `db_schema.md` - 完整的数据库表结构
- `task.md` - 任务清单
- `AGENTS.md` - 开发规范

---

**注意：** 原 GETTING_STARTED.md 包含多租户内容（organizations），请忽略那些部分，使用本文档。
