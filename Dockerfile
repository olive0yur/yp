# 使用多阶段构建优化
FROM php:7.4-fpm as base

# 设置环境变量
ENV DEBIAN_FRONTEND=noninteractive

# 安装必要的系统依赖
RUN echo "deb http://mirrors.tuna.tsinghua.edu.cn/debian buster main contrib non-free" > /etc/apt/sources.list \
    && echo "deb http://mirrors.tuna.tsinghua.edu.cn/debian buster-updates main contrib non-free" >> /etc/apt/sources.list \
    && echo "deb http://mirrors.tuna.tsinghua.edu.cn/debian-security buster-security main contrib non-free" >> /etc/apt/sources.list \
    && apt-get update && apt-get install -y \
       libonig-dev \
       libpng-dev \
       libfreetype6-dev \
       libjpeg62-turbo-dev \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# 安装核心PHP扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
    && pecl install redis swoole \
    && docker-php-ext-enable redis swoole

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /var/www

# 先复制composer文件，利用Docker缓存
COPY composer.json composer.lock ./

# 安装 PHP 依赖（利用缓存）
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 复制项目文件
COPY . /var/www

# 运行composer脚本
RUN composer run-script post-autoload-dump

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
echo "启动ThinkPHP应用..."\n\
cd /var/www/public\n\
exec php -S 0.0.0.0:8000 -t /var/www/public' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

# 启动服务
CMD ["/usr/local/bin/start.sh"]