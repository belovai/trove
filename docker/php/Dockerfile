FROM php:8.4-fpm

WORKDIR /var/www

# Build/runtime libraries.
#   gd (jpeg/png/webp/avif/freetype) -> Intervention Image, thumbnails
#   libmagickwand-dev                -> imagick, animated WebP/GIF frame extraction
#   libpq-dev                        -> pdo_pgsql (optional PostgreSQL deployment)
#   libsqlite3-dev                   -> pdo_sqlite (default database)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
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

COPY php.ini /usr/local/etc/php/conf.d/99-trove.ini
COPY php-fpm.conf /usr/local/etc/php-fpm.d/zz-trove.conf
COPY supervisord.conf /etc/supervisor/supervisord.conf
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Match the container user to the host user so bind-mounted files stay writable.
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g "$GID" www-data \
    && usermod -o -u "$UID" -g "$GID" -s /bin/bash www-data

ENV COMPOSER_HOME=/tmp/composer

# The queue worker command supervisor runs. `queue:work` keeps the framework
# booted, so it does not see code changes; compose.override.yaml swaps in
# `queue:listen` for development, which reboots per job.
ENV TROVE_QUEUE_COMMAND="queue:work --sleep=1 --tries=3 --max-time=3600"

ENTRYPOINT ["docker-entrypoint.sh"]
EXPOSE 9000
CMD ["start"]
