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
WORKDIR /var/www

# Копіювання всього проекту в контейнер
COPY . .

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Копіювання конфігураційних файлів
COPY .env.example .env

# Створення папки для кешу та прав доступу
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Експонування порту 9000
EXPOSE 9000

# Запуск PHP-FPM
CMD ["php-fpm"]
