#!/bin/sh
set -e

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Clearing caches..."
php artisan optimize:clear
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-v7.php

echo "==> Caching with runtime env..."
php artisan config:cache
php artisan route:cache

echo "==> Running migrations..."
php artisan migrate --force

php artisan storage:link || true

echo "==> Verifying session config:"
php artisan tinker --execute="dump(config('session.same_site'));" || true

exec apache2-foreground