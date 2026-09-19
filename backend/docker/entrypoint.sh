#!/bin/sh
set -e

# Fail fast if Postgres is still waking (Render free sleeps the DB too).
export PGCONNECT_TIMEOUT="${PGCONNECT_TIMEOUT:-8}"

php artisan config:clear

# CRITICAL: open the HTTP port ASAP.
# Otherwise Render stays on "Application loading" forever while migrate waits on a sleeping DB.
echo "Starting Laravel on port ${PORT:-8000}..."
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" &
SERVER_PID=$!

migrate_ready=0
i=0
echo "Waiting for database / running migrations..."
while true; do
  if php artisan migrate --force --no-interaction; then
    migrate_ready=1
    break
  fi
  i=$((i + 1))
  if [ "$i" -ge 40 ]; then
    echo "WARNING: database not ready after ${i} attempts. App is listening but DB may fail." >&2
    break
  fi
  echo "Migrate not ready yet (attempt ${i}/40). Retrying in 3s..."
  sleep 3
done

# Seed only when explicitly enabled (first deploy). Do NOT reseed on every cold start.
if [ "$migrate_ready" = "1" ] && [ "${RUN_SEED:-false}" = "true" ]; then
  echo "Seeding database (RUN_SEED=true)..."
  php artisan db:seed --force --no-interaction || echo "WARNING: seed failed" >&2
fi

# Best-effort caches (don't kill the already-running server).
php artisan config:cache || true
php artisan route:cache || true

echo "Server ready (pid ${SERVER_PID})."
wait "${SERVER_PID}"
