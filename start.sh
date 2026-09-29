#!/bin/sh
set -e

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "==> Caching with runtime env..."
php artisan config:cache
php artisan route:cache

echo "==> Running migrations..."
php artisan migrate --force

php artisan storage:link || true

exec apache2-foreground