#!/bin/sh
set -e

echo "==> Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Linking storage..."
php artisan storage:link || true

echo "==> Starting Apache..."
exec apache2-foreground