#!/usr/bin/env bash
#
# Run this after every `git pull` to deploy. Safe to re-run: migrations and
# seeders are idempotent, and maintenance mode always gets turned back off
# (via the trap below) even if a step in the middle fails.
#
# Usage: ./deploy.sh

set -euo pipefail

APP_DIR="/var/www/tmmtravels"
PHP_FPM_SERVICE="php8.5-fpm"
HORIZON_SUPERVISOR_PROGRAM="wma-bot-horizon"

cd "$APP_DIR"

echo "==> Entering maintenance mode"
php artisan down --render="errors::503" --retry=60

# No matter how this script exits (success, failure, or Ctrl-C), always try
# to bring the site back up rather than leaving it stuck behind the 503 page.
cleanup() {
    echo "==> Bringing site back up"
    php artisan up || true
}
trap cleanup EXIT

echo "==> Pulling latest code"
git fetch origin
git checkout main
git pull origin main

echo "==> Installing composer dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Installing frontend dependencies"
npm ci

echo "==> Building frontend assets"
npm run build

echo "==> Running migrations"
php artisan migrate --force

echo "==> Seeding defaults (safe: updateOrCreate, won't overwrite your changes)"
php artisan db:seed --force

echo "==> Rebuilding caches"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Reloading nginx and PHP-FPM"
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl reload "$PHP_FPM_SERVICE"

echo "==> Gracefully restarting Horizon (finishes in-flight jobs, Supervisor restarts it on the new code)"
php artisan horizon:terminate

echo "==> Waiting for Supervisor to bring Horizon back up"
sleep 5

echo "==> Verifying Horizon came back up"
STATUS=$(sudo supervisorctl status "${HORIZON_SUPERVISOR_PROGRAM}:*")
echo "$STATUS"
if ! echo "$STATUS" | grep -q RUNNING; then
    echo "!! Horizon is NOT running under Supervisor — check storage/logs/horizon.log"
    exit 1
fi

HORIZON_STATUS=$(php artisan horizon:status)
echo "$HORIZON_STATUS"
if ! echo "$HORIZON_STATUS" | grep -qi "running"; then
    echo "!! horizon:status did not report running"
    exit 1
fi

echo "==> Deploy complete."
