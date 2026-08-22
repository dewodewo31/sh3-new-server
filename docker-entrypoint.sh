#!/usr/bin/env bash
set -euo pipefail

# Load .env so DB/Redis connection values are available to this script.
# Snapshot pre-existing env first: .env baked into the image may hold empty
# placeholders (e.g. GOOGLE_DRIVE_API_KEY=) that must not override
# runtime-injected values from docker-compose.
declare -A PRESET_ENV=()
while IFS='=' read -r k v; do
  PRESET_ENV["$k"]="$v"
done < <(env)

set -a
. /var/www/html/.env
set +a

for k in "${!PRESET_ENV[@]}"; do
  export "$k=${PRESET_ENV[$k]}"
done

# `php artisan serve` strips worker env down to a passthrough whitelist,
# so runtime-injected vars must be persisted into .env for web requests.
if [ -n "${GOOGLE_DRIVE_API_KEY:-}" ]; then
  sed -i "s|^GOOGLE_DRIVE_API_KEY=.*|GOOGLE_DRIVE_API_KEY=${GOOGLE_DRIVE_API_KEY}|" /var/www/html/.env
fi

export DB_HOST="${DB_HOST:-mysql}"
export DB_PORT="${DB_PORT:-3306}"
export DB_NAME="${DB_DATABASE:-db_server_new}"
export DB_USER="${DB_USERNAME:-root}"
export DB_PASS="${DB_PASSWORD:-database_pass}"

echo "Waiting for MySQL (${DB_HOST}:${DB_PORT})..."
until php -r '
  try {
    $pdo = new PDO(
      "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_NAME").";charset=utf8mb4",
      getenv("DB_USER"), getenv("DB_PASS"),
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
  } catch (\Throwable $e) { exit(1); }
' 2>/dev/null; do
  sleep 2
done
echo "MySQL is ready."

php artisan config:clear
php artisan migrate --force

# Seed only on a fresh database (avoid duplicate seeds on container restart)
USER_COUNT=$(php -r '
  try {
    $pdo = new PDO(
      "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_NAME").";charset=utf8mb4",
      getenv("DB_USER"), getenv("DB_PASS")
    );
    echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  } catch (\Throwable $e) { echo "0"; }
' 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ]; then
  echo "Seeding database..."
  php artisan db:seed --force
fi

php artisan storage:link || true

# Queue worker (notifications) + scheduler (event status transitions) in background,
# HTTP server in foreground.
php artisan queue:work --queue=default --sleep=2 --tries=3 --timeout=300 &
php artisan schedule:work &
exec php artisan serve --host=0.0.0.0 --port=8000
