#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Running composer install..."
composer install --no-dev --optimize-autoloader

echo "Clearing caches..."
php artisan cache:clear

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching views..."
php artisan view:cache

echo "Caching Filament components..."
php artisan filament:cache-components

echo "Running migrations..."
php artisan migrate --force

echo "Build complete!"
