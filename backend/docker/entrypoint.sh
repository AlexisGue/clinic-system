#!/bin/sh
set -e

php artisan config:clear

php artisan migrate --force

if [ "${RUN_SEED:-true}" = "true" ]; then
  php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
