FROM php:7.4-fpm

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 安装 PHP 扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache

# 安装 Redis 扩展
RUN pecl install redis && docker-php-ext-enable redis

# 安装 Swoole 扩展
RUN pecl install swoole && docker-php-ext-enable swoole

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /var/www

# 复制项目文件
COPY . /var/www

# 安装 PHP 依赖
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 创建必要的目录
RUN mkdir -p /var/www/runtime/log \
    && mkdir -p /var/www/runtime/session \
    && mkdir -p /var/www/runtime/temp \
    && mkdir -p /var/www/public/storage

# 设置权限
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod -R 777 /var/www/runtime \
    && chmod -R 777 /var/www/public/storage

# 配置 PHP-FPM 监听端口为 8000
RUN sed -i 's/listen = 9000/listen = 8000/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;listen.owner = www-data/listen.owner = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;listen.group = www-data/listen.group = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;listen.mode = 0660/listen.mode = 0660/' /usr/local/etc/php-fpm.d/www.conf

# 配置 PHP
RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/docker-php-memory.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/docker-php-upload.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/docker-php-upload.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/docker-php-time.ini \
    && echo "date.timezone = Asia/Shanghai" >> /usr/local/etc/php/conf.d/docker-php-timezone.ini

# 配置 OPcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini

EXPOSE 8000

# 创建启动脚本
RUN echo '#!/bin/bash\n\
# 等待数据库和Redis启动\n\
echo "等待数据库和Redis服务启动..."\n\
sleep 10\n\
\n\
# 启动PHP内置服务器（HTTP）\n\
cd /var/www/public\n\
php -S 0.0.0.0:8000 -t /var/www/public' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

# 启动服务
CMD ["/usr/local/bin/start.sh"]