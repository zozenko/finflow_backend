FROM php:8.3-fpm

# Системні залежності
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    git \
    curl

# Розширення для PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Копіюємо файли проекту
COPY . .

# Права доступу для Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Запуск вбудованого сервера Laravel для розробки
CMD php artisan serve --host=0.0.0.0 --port=8000
