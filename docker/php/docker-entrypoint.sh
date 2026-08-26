#!/bin/bash
set -e

as_app() { gosu_uid=$(id -u www-data); setpriv --reuid="$gosu_uid" --regid="$(id -g www-data)" --clear-groups "$@"; }

bootstrap() {
    cd /var/www

    if [ ! -f .env ] && [ -f .env.example ]; then
        cp .env.example .env
        chown www-data:www-data .env
    fi

    if [ ! -d vendor ]; then
        echo "vendor/ missing, running composer install"
        rm -f bootstrap/cache/*.php
        as_app composer install --no-interaction --prefer-dist
    fi

    # SQLite is the default, zero-dependency database.
    if grep -qsE '^DB_CONNECTION=sqlite' .env && [ ! -f database/database.sqlite ]; then
        touch database/database.sqlite
    fi

    mkdir -p \
        storage/framework/{cache/data,sessions,views} \
        storage/logs \
        storage/app/{public,private} \
        bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache database

    if [ -f artisan ] && ! grep -qsE '^APP_KEY=.+' .env; then
        as_app php artisan key:generate --force
    fi

    # Self-hosted app: the container owns its schema, so migrate on boot.
    # Set TROVE_AUTO_MIGRATE=false to manage migrations yourself.
    if [ -f artisan ] && [ "${TROVE_AUTO_MIGRATE:-true}" = "true" ]; then
        as_app php artisan migrate --force --graceful
    fi
}

case "$1" in
    start)
        bootstrap
        exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
        ;;
    bootstrap)
        bootstrap
        ;;
    *)
        exec "$@"
        ;;
esac
