# 系统监控平台 - 分步实施指南

这是一份完整的、从零开始的实施指南。按照这个文档一步步操作，你将搭建起一个完整的监控系统。

> **预计时间**：5-7 天（每天 4-6 小时）
> **难度**：中等（需要 Laravel 和数据库基础）

---

## 准备工作

### 环境检查清单

在开始之前，确保你的系统已安装：

```bash
# 检查 PHP 版本（需要 >= 8.2）
php -v

# 检查 Composer
composer --version

# 检查 Node.js（需要 >= 18）
node -v
npm -v

# 检查 PostgreSQL（需要 >= 14）
psql --version

# 检查 Redis
redis-cli --version
```

如果缺少任何组件，请先安装：

**Ubuntu/Debian:**
```bash
# PHP 8.2
sudo apt install php8.2 php8.2-fpm php8.2-cli php8.2-pgsql php8.2-redis php8.2-mbstring php8.2-xml php8.2-curl

# PostgreSQL
sudo apt install postgresql postgresql-contrib

# Redis
sudo apt install redis-server

# Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs
```

**macOS:**
```bash
brew install php@8.2
brew install postgresql@14
brew install redis
brew install node@18
```

---

## 第 1 天：环境搭建

### 步骤 1.1：安装 Laravel 依赖

```bash
cd backend
composer install
```

**可能遇到的问题：**
- ❌ `composer install` 很慢：配置国内镜像
  ```bash
  composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
  ```

### 步骤 1.2：配置环境变量

```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env` 文件：

```env
APP_NAME="Agent Monitor"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# PostgreSQL 数据库配置
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=agent_monitor
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Redis 配置
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 队列配置
QUEUE_CONNECTION=redis

# 广播配置（WebSocket）
BROADCAST_CONNECTION=reverb

# 日志
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### 步骤 1.3：创建数据库

```bash
# 连接到 PostgreSQL
sudo -u postgres psql

# 在 psql 中执行：
CREATE DATABASE agent_monitor;
CREATE USER monitor_user WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE agent_monitor TO monitor_user;

# 退出
\q
```

测试连接：
```bash
php artisan db:show
```

### 步骤 1.4：安装 WebSocket 支持

```bash
composer require laravel/reverb
php artisan reverb:install
```

这会自动添加配置到 `.env`。

### 步骤 1.5：安装前端依赖

```bash
npm install
```

### 步骤 1.6：测试启动

在 **4 个不同的终端** 中分别运行：

```bash
# 终端 1：Laravel 服务器
php artisan serve

# 终端 2：WebSocket 服务器
php artisan reverb:start

# 终端 3：队列处理
php artisan queue:work

# 终端 4：前端开发服务器
npm run dev
```

✅ **检查点**：访问 http://localhost:8000 能看到 Laravel 欢迎页面

---

## 第 2 天：数据库设计与迁移

### 步骤 2.1：创建组织（租户）表

```bash
```


```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('plan')->default('free');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('slug');
        });
    }

    public function down(): void
    {
    }
};
```

### 步骤 2.2：创建组织成员关联表

```bash
```

```php
public function up(): void
{
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('role')->default('viewer'); // owner, admin, viewer
        $table->timestamp('joined_at')->nullable();
        $table->timestamps();

    });
}
```

### 步骤 2.3：创建 API Keys 表

```bash
php artisan make:migration create_api_keys_table
```

```php
public function up(): void
{
    Schema::create('api_keys', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('key')->unique();
        $table->boolean('enabled')->default(true);
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();

        $table->index('key');
    });
}
```

### 步骤 2.4：创建 Agents 表（核心）

```bash
php artisan make:migration create_agents_table
```

```php
public function up(): void
{
    Schema::create('agents', function (Blueprint $table) {
        $table->id();
        $table->string('agent_id')->unique(); // UUID
        $table->foreignId('api_key_id')->nullable()->constrained()->onDelete('set null');
        $table->string('name');
        $table->string('hostname');
        $table->string('os', 50);
        $table->string('arch', 50);
        $table->string('version', 50);
        $table->enum('status', ['online', 'offline', 'unknown'])->default('unknown');
        $table->timestamp('last_heartbeat_at')->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->json('tags')->nullable();
        $table->timestamps();

        $table->index('agent_id');
        $table->index('last_heartbeat_at');
    });
}
```

### 步骤 2.5：创建 Metrics 表（时序数据）

```bash
php artisan make:migration create_metrics_table
```

```php
public function up(): void
{
    Schema::create('metrics', function (Blueprint $table) {
        $table->id();
        $table->foreignId('agent_id')->constrained()->onDelete('cascade');
        $table->string('metric_type', 50); // cpu, memory, disk, network, etc.
        $table->json('data'); // 指标的 JSON 数据
        $table->timestamp('collected_at'); // 采集时间（来自 Agent）
        $table->timestamp('created_at')->nullable(); // 接收时间（服务器）

        // 复合索引（最重要）
        $table->index(['agent_id', 'metric_type', 'collected_at'], 'idx_agent_type_time');
        $table->index('collected_at');
    });
}
```

### 步骤 2.6：执行迁移

```bash
php artisan migrate
```


---

## 第 3 天：模型与认证

### 步骤 3.1：创建模型

```bash
php artisan make:model Organization
php artisan make:model ApiKey
php artisan make:model Agent
php artisan make:model Metric
```

### 步骤 3.2：配置 Organization 模型

编辑 `app/Models/Organization.php`：

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'plan',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }
}
```

### 步骤 3.3：配置 ApiKey 模型

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $fillable = [
        'name',
        'key',
        'enabled',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'key', // 不在 API 响应中暴露
    ];

    {
        return $this->belongsTo(Organization::class);
    }

    public function isValid(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
```

### 步骤 3.4：配置 Agent 模型

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    protected $fillable = [
        'agent_id',
        'api_key_id',
        'name',
        'hostname',
        'os',
        'arch',
        'version',
        'status',
        'last_heartbeat_at',
        'ip_address',
        'tags',
    ];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'tags' => 'array',
    ];

    {
        return $this->belongsTo(Organization::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function isOnline(): bool
    {
        return $this->last_heartbeat_at
            && $this->last_heartbeat_at->gt(now()->subMinutes(2));
    }

    public function markOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    // Scopes
    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }
}
```

### 步骤 3.5：配置 Metric 模型

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    const UPDATED_AT = null; // 不需要 updated_at

    protected $fillable = [
        'agent_id',
        'metric_type',
        'data',
        'collected_at',
    ];

    protected $casts = [
        'data' => 'array',
        'collected_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // Scopes
    public function scopeInTimeRange($query, $start, $end)
    {
        return $query->whereBetween('collected_at', [$start, $end]);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('metric_type', $type);
    }
}
```

### 步骤 3.6：创建 API Key 认证中间件

```bash
php artisan make:middleware ApiKeyAuth
```

编辑 `app/Http/Middleware/ApiKeyAuth.php`：

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'API key is required'
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)->first();

        if (!$key || !$key->isValid()) {
            return response()->json([
                'error' => 'Invalid or expired API key'
            ], 401);
        }

        // 更新最后使用时间
        $key->update(['last_used_at' => now()]);

        // 注入到请求中
        $request->merge([
            'api_key_id' => $key->id,
        ]);

        return $next($request);
    }
}
```

### 步骤 3.7：注册中间件

编辑 `bootstrap/app.php`：

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
    ]);
})
```

✅ **检查点**：模型和中间件创建完成

---

## 第 4 天：Agent API 开发

### 步骤 4.1：创建 Agent 控制器

```bash
php artisan make:controller API/AgentController
```

编辑 `app/Http/Controllers/API/AgentController.php`：

```php
<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * 探针注册
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'agentInfo' => 'required|array',
            'agentInfo.id' => 'required|string',
            'agentInfo.name' => 'required|string',
            'agentInfo.hostname' => 'required|string',
            'agentInfo.os' => 'required|string',
            'agentInfo.arch' => 'required|string',
            'agentInfo.version' => 'required|string',
        ]);

        $agentInfo = $validated['agentInfo'];

        $agent = Agent::updateOrCreate(
            ['agent_id' => $agentInfo['id']],
            [
                'name' => $agentInfo['name'],
                'hostname' => $agentInfo['hostname'],
                'os' => $agentInfo['os'],
                'arch' => $agentInfo['arch'],
                'version' => $agentInfo['version'],
                'status' => 'online',
                'last_heartbeat_at' => now(),
                'ip_address' => $request->ip(),
                'api_key_id' => $request->input('api_key_id'),
            ]
        );

        return response()->json([
            'agentId' => $agent->agent_id,
            'status' => 'success',
            'message' => 'Agent registered successfully'
        ]);
    }

    /**
     * 心跳
     */
    public function heartbeat(Request $request)
    {
        $validated = $request->validate([
            'agentId' => 'required|string',
        ]);

        $agent = Agent::where('agent_id', $validated['agentId'])->first();

        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $agent->update([
            'status' => 'online',
            'last_heartbeat_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### 步骤 4.2：创建路由

编辑 `routes/api.php`，添加：

```php
use App\Http\Controllers\API\AgentController;

Route::prefix('agent')->middleware('api.key')->group(function () {
    Route::post('/register', [AgentController::class, 'register']);
    Route::post('/heartbeat', [AgentController::class, 'heartbeat']);
});
```

### 步骤 4.3：创建测试数据

```bash
php artisan tinker
```

在 tinker 中执行：

```php
// 创建组织
$org = \App\Models\Organization::create([
    'name' => 'Test Organization',
    'slug' => 'test-org',
    'plan' => 'free',
]);

// 创建 API Key
$key = \App\Models\ApiKey::create([
    'name' => 'Test Key',
    'key' => \Illuminate\Support\Str::random(32),
    'enabled' => true,
]);

echo "API Key: " . $key->key . "\n";
// 复制这个 key，后面要用
```

### 步骤 4.4：测试 Agent 注册

```bash
curl -X POST http://localhost:8000/api/agent/register \
  -H "X-API-Key: YOUR_API_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "agentInfo": {
      "id": "test-agent-001",
      "name": "Test Server",
      "hostname": "localhost",
      "os": "linux",
      "arch": "amd64",
      "version": "1.0.0"
    }
  }'
```

**期望输出：**
```json
{
  "agentId": "test-agent-001",
  "status": "success",
  "message": "Agent registered successfully"
}
```

### 步骤 4.5：测试心跳

```bash
curl -X POST http://localhost:8000/api/agent/heartbeat \
  -H "X-API-Key: YOUR_API_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "agentId": "test-agent-001"
  }'
```

✅ **检查点**：Agent 可以成功注册和发送心跳

---

## 第 5 天：指标采集

### 步骤 5.1：创建 Metric 控制器

```bash
php artisan make:controller API/MetricController
```

```php
<?php
namespace App\Http\Controllers\API\MetricController;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Metric;
use Illuminate\Http\Request;

class MetricController extends Controller
{
    /**
     * 存储探针上报的指标
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agentId' => 'required|string',
            'metrics' => 'required|array',
            'metrics.*.type' => 'required|string',
            'metrics.*.data' => 'required|array',
            'metrics.*.collectedAt' => 'required|date',
        ]);

        $agent = Agent::where('agent_id', $validated['agentId'])->first();

        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $metrics = [];
        foreach ($validated['metrics'] as $metricData) {
            $metric = Metric::create([
                'agent_id' => $agent->id,
                'metric_type' => $metricData['type'],
                'data' => $metricData['data'],
                'collected_at' => $metricData['collectedAt'],
            ]);
            $metrics[] = $metric;
        }

        return response()->json([
            'status' => 'success',
            'received' => count($metrics)
        ]);
    }

    /**
     * 查询指标数据
     */
    public function index(Request $request, Agent $agent)
    {
        $type = $request->input('type');
        $start = $request->input('start', now()->subHour());
        $end = $request->input('end', now());

        $query = $agent->metrics()
            ->inTimeRange($start, $end)
            ->orderBy('collected_at', 'desc');

        if ($type) {
            $query->ofType($type);
        }

        return response()->json([
            'data' => $query->limit(100)->get()
        ]);
    }
}
```

### 步骤 5.2：添加路由

```php
// 在 routes/api.php 中添加
Route::prefix('agent')->middleware('api.key')->group(function () {
    Route::post('/register', [AgentController::class, 'register']);
    Route::post('/heartbeat', [AgentController::class, 'heartbeat']);
    Route::post('/metrics', [MetricController::class, 'store']); // 新增
});

// 管理端查询（需要 Sanctum 认证，暂时先不加）
Route::get('/agents/{agent}/metrics', [MetricController::class, 'index']);
```

### 步骤 5.3：测试指标上报

```bash
curl -X POST http://localhost:8000/api/agent/metrics \
  -H "X-API-Key: YOUR_API_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "agentId": "test-agent-001",
    "metrics": [
      {
        "type": "cpu",
        "data": {
          "usagePercent": 45.2,
          "cores": 8,
          "modelName": "Intel Core i7"
        },
        "collectedAt": "2025-11-22T10:00:00Z"
      },
      {
        "type": "memory",
        "data": {
          "total": 16384,
          "used": 8192,
          "free": 8192,
          "usagePercent": 50
        },
        "collectedAt": "2025-11-22T10:00:00Z"
      }
    ]
  }'
```

### 步骤 5.4：验证数据

```bash
php artisan tinker
```

```php
// 查看刚才插入的数据
\App\Models\Metric::latest()->take(5)->get();
```

✅ **检查点**：指标数据可以成功上报并存储到数据库

---

## 第 6 天：WebSocket 实时推送

### 步骤 6.1：创建广播事件

```bash
php artisan make:event MetricsReceived
```

编辑 `app/Events/MetricsReceived.php`：

```php
<?php
namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MetricsReceived implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $agent;
    public $metrics;

    public function __construct(Agent $agent, array $metrics)
    {
        $this->agent = $agent;
        $this->metrics = $metrics;
    }

    public function broadcastOn()
    {
    }

    public function broadcastWith()
    {
        return [
            'agent_id' => $this->agent->agent_id,
            'agent_name' => $this->agent->name,
            'metrics' => $this->metrics,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

### 步骤 6.2：在 MetricController 中触发事件

修改 `MetricController::store()` 方法，在返回前添加：

```php
// 触发 WebSocket 广播
broadcast(new \App\Events\MetricsReceived($agent, $metrics));

return response()->json([
    'status' => 'success',
    'received' => count($metrics)
]);
```

### 步骤 6.3：启动 Reverb 并测试

确保 Reverb 正在运行：
```bash
php artisan reverb:start --debug
```

再次上报指标，你应该在 Reverb 终端看到广播日志。

✅ **检查点**：指标上报时 WebSocket 广播正常

---

## 第 7 天：对接 Go Agent

### 步骤 7.1：配置 Go Agent

在 Go Agent 项目目录创建配置文件 `config.yaml`：

```yaml
server_url: "http://localhost:8000"
api_key: "YOUR_API_KEY_HERE"  # 从 Laravel 获取的 key
heartbeat_interval: 30
metrics_interval: 60

agent:
  name: "My Server"
  # 其他配置...
```

### 步骤 7.2：修改 Go Agent 代码（可选）

如果需要修改 Go Agent 使其对接 Laravel API，参考 `AGENTS.md` 中的说明。

### 步骤 7.3：运行 Go Agent

```bash
cd <your-agent-directory>
go run cmd/agent/main.go --config=config.yaml
```

观察 Laravel 日志，确认 Agent 成功注册和上报数据。

✅ **检查点**：Go Agent 可以成功连接 Laravel 并上报数据

---

## 下一步

恭喜！🎉 你已经完成了监控系统的核心功能。

**已完成：**
- ✅ 环境搭建
- ✅ 数据库设计
- ✅ Agent 认证
- ✅ 指标采集
- ✅ WebSocket 实时推送
- ✅ Go Agent 对接

**后续可以继续：**
1. 创建监控任务功能（HTTP/TCP 监控）
2. 实现告警系统
3. 开发 Web 管理界面
4. 性能优化（TimescaleDB、缓存等）

详细的后续任务请参考 `task.md`。

---

## 常见问题

### Q: PostgreSQL 连接失败？
A: 检查 `.env` 配置，确保数据库已创建，用户有权限。

### Q: WebSocket 无法连接？
A: 确保 Reverb 正在运行，检查防火墙设置。

### Q: 指标数据没有插入？
A: 检查 Laravel 日志 `storage/logs/laravel.log`

### Q: Agent 注册失败？
A: 确认 API Key 正确，检查中间件是否正常工作。

---

**需要帮助？** 随时问我！🚀
