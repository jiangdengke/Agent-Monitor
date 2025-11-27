# Repository Guidelines

## 项目结构与模块
- `app/`：Laravel 11 业务代码，控制器在 `app/Http/Controllers`，请求验证在 `app/Http/Requests`，模型在 `app/Models`；新增业务逻辑时建议按领域拆分子目录，保持控制器瘦身。
- `routes/api.php`：所有 API 路由；`routes/web.php` 仅用于页面/调试。
- `resources/`：前端视图与 Vite 资产入口；多语言文案存放在顶层 `lang/`。
- `database/migrations` 与 `database/seeders`：结构与种子数据；本地变更需附带迁移说明。
- `tests/Feature`、`tests/Unit`：PHPUnit 测试；`phpunit.xml` 已预置覆盖率配置。
- `docs/`、`README.md` 等：背景与操作手册，提交前请更新相关文档。

## 本地开发与构建
```bash
composer install          # 安装 PHP 依赖
cp .env.example .env && php artisan key:generate
php artisan migrate       # 初始化数据库（PostgreSQL）
php artisan serve         # 开发模式 HTTP 服务
php artisan octane:start  # WebSocket/高并发服务（需 swoole 扩展）
php artisan queue:work    # 队列消费者
npm install && npm run dev # 如需前端资产编译
```
脚本 `composer dev` 可并行启动 serve/queue/logs/vite，方便一键开发。

## 代码风格与命名
- 遵循 PSR-12，PHP 使用 4 空格缩进；控制器/请求/Job 类名用 `PascalCase`，方法与变量用 `camelCase`。
- 迁移文件名与表字段使用 `snake_case`，枚举值与业务常量集中管理，配合 `jiannei/laravel-enum`；返回体保持 `jiannei/laravel-response` 的规范格式。
- 格式化使用 `./vendor/bin/pint`；提交前执行以保持一致性。

## 架构与约定
- 服务入口为 HTTP API + WebSocket（Octane/Swoole），需要长连接的功能放在 Octane，短连接走 `php artisan serve` 即可。
- 推荐控制器仅负责验证与调度，业务放在 `app/Services` 或 `app/Actions`（可按功能新建目录），数据库访问通过 Eloquent 模型或 Repository 封装。
- 队列任务与事件监听请放入 `app/Jobs`、`app/Listeners`，并确保队列工作器启动后再推送任务。

## 测试准则
- 测试框架：PHPUnit（`php artisan test` 或 `./vendor/bin/phpunit`）。
- Feature 测试覆盖 API 行为，Unit 测试聚焦纯业务方法；文件命名 `SomethingTest.php`，类名与被测对象保持一致前缀。
- 含数据库交互的测试优先使用 `RefreshDatabase`/`DatabaseTransactions`，必要时提供工厂或种子数据；新功能至少附带一条可复现主流程的 Feature 测试。

## 提交与 Pull Request
- 提交信息采用 Conventional Commits：如 `feat(api): 添加 Agent 鉴权`、`fix(queue): 修复心跳任务间隔`。
- PR 需包含：变更概要、测试结果（命令输出简述）、关联 Issue/需求单、如涉及接口或界面请附示例请求或截图。
- 变更迁移或配置时，请在描述中标注需要的运维步骤（如运行 `php artisan migrate`、安装扩展）。

## 安全与配置
- `.env` 含密钥与数据库凭据，严禁提交；本地调试使用 `.env.example` 复制生成。
- 默认依赖 PostgreSQL、Redis 与 Swoole 扩展，缺失组件会导致启动失败；变更环境需求时同步更新 README、`.env.example` 与配置样例。
- 对外接口需保持 API Key 校验与速率限制，新增路由请确认中间件链路与权限策略。
- 日志位于 `storage/logs/laravel.log`，排查时可配合 `php artisan route:list`、`php artisan queue:failed`、`php artisan config:clear` 等命令快速定位问题；长期运行场景建议开启进程监控与健康检查。
