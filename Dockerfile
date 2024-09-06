# Вибір базового образу PHP
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    nginx \
    build-essential \
    openssl

RUN docker-php-ext-install gd pdo pdo_mysql pdo_pgsql sockets zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Створення робочого каталогу
WORKDIR /var/www/main-portal

# Копіювання всього проекту в контейнер
COPY . .

COPY composer.json composer.lock ./
# Перевірка наявності composer.json
RUN ls -la && cat composer.json
RUN composer self-update && \
    composer config http-basic.nova.laravel.com glapacha@gmail.com UxDAKzcXTH1fb2pQIbzVcnpcLzq2RvJD8BCOK5dPspVhFJZ3Rp && \
    composer install --no-dev --optimize-autoloader --no-scripts

# Копіювання конфігураційних файлів
COPY .env.example .env

# Створення папки для кешу та прав доступу
RUN chown -R www-data:www-data /var/www/main-portal \
    && chmod -R 755 /var/www/main-portal/storage \
    && chmod -R 755 /var/www/main-portal/bootstrap/cache

# Експонування порту 9000
#EXPOSE 9000

# Запуск PHP-FPM
#CMD ["php-fpm"]
