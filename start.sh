#!/bin/bash

# 启动所有服务的脚本

echo "启动后端服务..."

# 启动 PHP 后端 API
php artisan serve &
PHP_PID=$!

# 启动 Reverb WebSocket
php artisan reverb:start &
REVERB_PID=$!

# 启动定时任务调度器
php artisan schedule:work &
SCHEDULE_PID=$!

# 启动 Go 探针
cd agent && ./agent &
AGENT_PID=$!
cd ..

# 启动前端
cd web && npm run dev &
WEB_PID=$!

echo ""
echo "所有服务已启动："
echo "  - PHP API (PID: $PHP_PID)"
echo "  - Reverb WebSocket (PID: $REVERB_PID)"
echo "  - Schedule Worker (PID: $SCHEDULE_PID)"
echo "  - Go Agent (PID: $AGENT_PID)"
echo "  - Frontend (PID: $WEB_PID)"
echo ""
echo "按 Ctrl+C 停止所有服务"

# 捕获 Ctrl+C 信号，停止所有进程
trap "kill $PHP_PID $REVERB_PID $SCHEDULE_PID $AGENT_PID $WEB_PID 2>/dev/null; exit" SIGINT SIGTERM

# 等待
wait
