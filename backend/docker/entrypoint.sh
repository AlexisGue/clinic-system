#!/bin/sh
set -e

# Fail fast if Postgres is still waking (Render free sleeps the DB too).
export PGCONNECT_TIMEOUT="${PGCONNECT_TIMEOUT:-8}"

# Free-tier cold boots start HTTP before migrations finish.
# Database session/cache drivers 500 every /api request if tables are missing.
# File drivers keep CSRF + throttling working while migrate catches up.
export SESSION_DRIVER="${SESSION_DRIVER_OVERRIDE:-file}"
export CACHE_STORE="${CACHE_STORE_OVERRIDE:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION_OVERRIDE:-sync}"

php artisan config:clear

# CRITICAL: open the HTTP port ASAP.
# Otherwise Render stays on "Application loading" forever while migrate waits on a sleeping DB.
echo "Starting Laravel on port ${PORT:-8000} (session=${SESSION_DRIVER}, cache=${CACHE_STORE})..."
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" &
SERVER_PID=$!

run_migrations() {
  i=0
  echo "Waiting for database / running migrations..."
  while true; do
    if php artisan migrate --force --no-interaction; then
      echo "Migrations OK."
      if [ "${RUN_SEED:-false}" = "true" ]; then
        echo "Seeding database (RUN_SEED=true)..."
        php artisan db:seed --force --no-interaction || echo "WARNING: seed failed" >&2
      fi
      php artisan config:clear || true
      php artisan config:cache || true
      php artisan route:cache || true
      return 0
    fi
    i=$((i + 1))
    echo "Migrate not ready yet (attempt ${i}). Retrying in 5s..."
    sleep 5
  done
}

# Keep retrying forever in background so a late DB wake still creates tables.
run_migrations &

echo "Server ready (pid ${SERVER_PID})."
wait "${SERVER_PID}"
