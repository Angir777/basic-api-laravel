#!/bin/sh

set -eu

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"

cd "$PROJECT_DIR"

echo "==> Installing production dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

echo "==> Clearing old caches..."
php artisan optimize:clear

echo "==> Caching configuration..."
php artisan config:cache

echo "==> Caching routes..."
php artisan route:cache

echo "==> Caching views..."
php artisan view:cache

echo "==> Deployment preparation completed."
