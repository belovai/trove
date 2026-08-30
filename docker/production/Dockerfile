# Single deployable image: nginx + php-fpm + queue worker + scheduler under
# supervisord, app code and built frontend assets baked in. One port (80).
# Build context is the repository root.

FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-fpm AS app
WORKDIR /var/www

# Build/runtime libraries.
#   gd (jpeg/png/webp/avif/freetype) -> Intervention Image, thumbnails
#   libmagickwand-dev                -> imagick, animated WebP/GIF frame extraction
#   libpq-dev                        -> pdo_pgsql (optional PostgreSQL deployment)
#   libsqlite3-dev                   -> pdo_sqlite (default database)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        nginx \
        supervisor \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libavif-dev \
        libmagickwand-dev \
        libpq-dev \
        libsqlite3-dev \
        sqlite3 \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        intl \
        zip \
        bcmath \
        exif \
        pcntl \
        pdo_sqlite \
        pdo_pgsql \
        pdo_mysql \
        opcache

# imagick: Intervention Image's alternative driver. Required for animated WebP,
# which GD cannot read. redis: optional cache/session/queue backend.
RUN pecl install imagick redis \
    && docker-php-ext-enable imagick redis

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-trove.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-trove.conf
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/production/nginx.conf /etc/nginx/conf.d/app.conf
COPY docker/production/supervisord.conf /etc/supervisor/supervisord.conf

ENV COMPOSER_HOME=/tmp/composer

# App code and built frontend assets are baked into the image, not
# bind-mounted — this is the deployable artifact, unlike docker/php/Dockerfile
# which only carries the runtime and expects the source on a mounted volume.
COPY . .
COPY --from=frontend /app/public/build /var/www/public/build

# --no-scripts: package:discover needs a booted app, which needs APP_KEY —
# none is baked into the image. Run it separately with a throwaway key; it
# only writes bootstrap/cache/{packages,services}.php, not the key itself.
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts \
    && rm -rf "$COMPOSER_HOME"
RUN APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')" \
        php artisan package:discover --ansi

RUN mkdir -p storage/framework/{cache/data,sessions,views} storage/logs \
        storage/app/{public,private} bootstrap/cache \
    && chown -R www-data:www-data /var/www

# The queue worker command supervisor runs. `queue:work` keeps the framework
# booted, so it does not see code changes — irrelevant here since code is
# baked in, but restart the container after deploying a new image tag.
ENV TROVE_QUEUE_COMMAND="queue:work --sleep=1 --tries=3 --max-time=3600"

ENTRYPOINT ["docker-entrypoint.sh"]
EXPOSE 80
CMD ["start"]
