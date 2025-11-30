FROM php:8.3-fpm-alpine

# 使用阿里云镜像源
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories

# 安装系统依赖
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS

# 安装 PHP 扩展
RUN docker-php-ext-install pdo pdo_pgsql pgsql pcntl bcmath gd

# 安装 Redis 扩展
RUN pecl install redis && docker-php-ext-enable redis

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /var/www/html

# 先复制 composer 文件以利用缓存
COPY composer.json composer.lock ./

# 安装 PHP 依赖（跳过 scripts 避免数据库连接问题）
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# 复制项目文件
COPY . .

# 设置权限
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 默认启动 PHP-FPM
CMD ["php-fpm"]
