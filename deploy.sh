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
SCHEDULER_CRON_FILE="/etc/cron.d/wma-bot-scheduler"
SCHEDULER_LOG="${APP_DIR}/storage/logs/scheduler.log"

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

echo "==> Installing Laravel scheduler cron"
# /etc/cron.d is used instead of a mutable user crontab so deployment remains
# repeatable and there can only be one scheduler entry for this application.
# flock also prevents two schedule:run processes overlapping if one invocation
# takes longer than expected.
SCHEDULER_CRON_TEMP=$(mktemp)
cleanup_scheduler_temp() {
    rm -f "$SCHEDULER_CRON_TEMP"
}
trap 'cleanup_scheduler_temp; cleanup' EXIT

{
    echo 'SHELL=/bin/bash'
    echo 'PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
    echo "* * * * * www-data cd ${APP_DIR} && /usr/bin/flock -n /tmp/wma-bot-scheduler.lock /usr/bin/php artisan schedule:run >> ${SCHEDULER_LOG} 2>&1"
} > "$SCHEDULER_CRON_TEMP"

sudo install -o root -g root -m 0644 "$SCHEDULER_CRON_TEMP" "$SCHEDULER_CRON_FILE"
cleanup_scheduler_temp
sudo touch "$SCHEDULER_LOG"
sudo chown www-data:www-data "$SCHEDULER_LOG"

if systemctl list-unit-files cron.service >/dev/null 2>&1; then
    sudo systemctl enable --now cron
elif systemctl list-unit-files crond.service >/dev/null 2>&1; then
    sudo systemctl enable --now crond
else
    echo "!! Neither cron.service nor crond.service is installed. Install cron and rerun deployment."
    exit 1
fi

echo "==> Verifying Laravel schedules"
php artisan schedule:list
if ! sudo grep -Fq "artisan schedule:run" "$SCHEDULER_CRON_FILE"; then
    echo "!! Laravel scheduler cron was not installed correctly"
    exit 1
fi

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

echo "==> Verifying scheduler service"
if systemctl list-unit-files cron.service >/dev/null 2>&1; then
    sudo systemctl is-active --quiet cron
else
    sudo systemctl is-active --quiet crond
fi
echo "Scheduler cron: $SCHEDULER_CRON_FILE"
echo "Scheduler log:  $SCHEDULER_LOG"

echo "==> Deploy complete."
