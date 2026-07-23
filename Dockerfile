# -----------------------------------------------------------------------------
# Stage 1: Install PHP dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-interaction

# -----------------------------------------------------------------------------
# Stage 2: Build frontend assets (Wayfinder needs PHP + Composer vendor)
# -----------------------------------------------------------------------------
FROM php:8.5-fpm-bookworm AS frontend

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        ca-certificates \
        gnupg \
        unzip \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        pcntl \
        pdo_mysql \
        zip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=vendor /app /app
COPY package.json package-lock.json ./

RUN npm ci

COPY . .

# Dummy key so Artisan (Wayfinder) can boot during the Vite build
ENV APP_KEY=base64:dGVtcG9yYXJ5LWtleS1mb3ItZG9ja2VyLWJ1aWxkLW9ubHk=
ENV APP_ENV=production

RUN php artisan wayfinder:generate --with-form \
    && npm run build \
    && rm -rf node_modules

# -----------------------------------------------------------------------------
# Stage 3: Runtime (Nginx + PHP-FPM)
# -----------------------------------------------------------------------------
FROM php:8.5-fpm-bookworm AS app

# mbstring + opcache ship with the PHP 8.5 image — do not reinstall them
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        libzip-dev \
        libpng-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

WORKDIR /var/www/html

COPY --from=frontend /app /var/www/html
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /var/log/supervisor \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
