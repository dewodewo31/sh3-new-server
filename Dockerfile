# syntax=docker/dockerfile:1

# ---------- Frontend assets (Vite build) ----------
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY . .
RUN npm run build

# ---------- PHP dependencies ----------
FROM composer:2 AS composer
WORKDIR /app
COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

# ---------- Application runtime ----------
FROM php:8.3-cli
RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip libpng-dev libfreetype6-dev libjpeg62-turbo-dev libonig-dev libxml2-dev libicu-dev libzip-dev \
      && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
      pdo pdo_mysql mbstring xml bcmath intl gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && echo "opcache.jit_buffer_size=64M" > /usr/local/etc/php/conf.d/opcache-jit.ini

WORKDIR /var/www/html

# Vendor + compiled frontend from build stages
COPY --from=composer /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Application source
COPY . .

# Configure .env for Docker (MySQL + Redis) per how-to-run.md
RUN cp .env.example .env \
 && sed -i \
      -e 's|^DB_CONNECTION=.*|DB_CONNECTION=mysql|' \
      -e 's|^# DB_HOST=127.0.0.1|DB_HOST=mysql|' \
      -e 's|^# DB_PORT=3306|DB_PORT=3306|' \
      -e 's|^# DB_DATABASE=laravel|DB_DATABASE=db_server_new|' \
      -e 's|^# DB_USERNAME=root|DB_USERNAME=root|' \
      -e 's|^# DB_PASSWORD=|DB_PASSWORD=database_pass|' \
      -e 's|^REDIS_HOST=.*|REDIS_HOST=redis|' \
      -e 's|^SESSION_DRIVER=.*|SESSION_DRIVER=redis|' \
      -e 's|^CACHE_STORE=.*|CACHE_STORE=redis|' \
      -e 's|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|' \
      -e 's|^APP_NAME=.*|APP_NAME="Samarinda Hash House Harriers"|' \
      -e 's|^APP_URL=.*|APP_URL=http://localhost:8000|' \
      .env \
 && php artisan key:generate

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
