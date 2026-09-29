#!/bin/sh
set -e

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Linking storage..."
php artisan storage:link || true

echo "==> Starting Apache..."
exec apache2-foreground