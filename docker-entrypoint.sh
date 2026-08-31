#!/bin/sh
set -e

# Run database migrations and seeders for demo accounts
php artisan migrate --force
php artisan db:seed --force

# Optimize Laravel routes, views and config for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Laravel using Render's provided $PORT
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
