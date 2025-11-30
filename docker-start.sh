#!/bin/bash

# Server Monitor Docker 启动脚本

set -e

echo "=========================================="
echo "  Server Monitor - Docker 环境启动"
echo "=========================================="

# 检查 .env 文件
if [ ! -f .env ]; then
    echo "[1/5] 创建 .env 配置文件..."
    cp .env.docker .env
else
    echo "[1/5] .env 文件已存在，跳过..."
fi

# 构建并启动容器
echo "[2/5] 构建并启动 Docker 容器..."
docker-compose up -d --build

# 等待数据库就绪
echo "[3/5] 等待数据库就绪..."
sleep 5

# 安装依赖并初始化
echo "[4/5] 初始化应用..."
docker-compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader
docker-compose exec -T app php artisan migrate --force
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear

echo "[5/5] 启动完成！"
echo ""
echo "=========================================="
echo "  服务访问地址："
echo "  - 后端 API:    http://localhost:8000"
echo "  - 前端页面:    http://localhost:5173"
echo "  - WebSocket:   ws://localhost:8080"
echo "=========================================="
echo ""
echo "常用命令："
echo "  docker-compose logs -f        # 查看日志"
echo "  docker-compose down           # 停止服务"
echo "  docker-compose restart        # 重启服务"
echo "  docker-compose exec app bash  # 进入容器"
