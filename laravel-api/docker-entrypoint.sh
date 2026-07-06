#!/bin/sh
set -e

echo "Waiting for database at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
until php -r "new PDO('mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306}', '${DB_USERNAME:-smsb_user}', '${DB_PASSWORD:-smsb_password}');" >/dev/null 2>&1; do
  sleep 2
done
echo "Database is up."

php artisan config:clear >/dev/null
php artisan migrate --force

# Seed only on first run: re-running the demo seeders would violate unique
# constraints (user emails, PO numbers) on every container restart.
ROLE_COUNT=$(php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count();" 2>/dev/null | tail -n 1)
if [ "$ROLE_COUNT" = "0" ]; then
  echo "Fresh database detected, seeding demo data..."
  php artisan db:seed --force
fi

php artisan storage:link >/dev/null 2>&1 || true

exec "$@"
