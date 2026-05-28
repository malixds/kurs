#!/bin/sh
set -e

cd /var/www/backend

if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
fi

mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R wellbeing:wellbeing storage bootstrap/cache vendor

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    su-exec wellbeing php artisan key:generate --force --no-interaction || true
fi

if [ "${APP_ENV:-local}" = "local" ]; then
    su-exec wellbeing php artisan config:clear --no-interaction || true
    su-exec wellbeing php artisan route:clear --no-interaction || true
else
    su-exec wellbeing php artisan config:cache --no-interaction || true
    su-exec wellbeing php artisan route:cache --no-interaction || true
fi

if [ "$1" = "php-fpm" ]; then
    exec php-fpm
fi

exec su-exec wellbeing "$@"
