
#!/usr/bin/env bash
# TMM Travels / WMA Bot production deployment
# Usage:
#   ./deploy.sh
#   RUN_SEEDERS=true ./deploy.sh
#   GENERATE_SHIELD=true ./deploy.sh

set -Eeuo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_FPM_SERVICE="php8.5-fpm"
HORIZON_SUPERVISOR_PROGRAM="wma-bot-horizon"
SCHEDULER_CRON_FILE="/etc/cron.d/wma-bot-scheduler"
SCHEDULER_LOG="${APP_DIR}/storage/logs/scheduler.log"

RUN_SEEDERS="${RUN_SEEDERS:-false}"
GENERATE_SHIELD="${GENERATE_SHIELD:-false}"

cd "$APP_DIR"

MAINTENANCE_ENABLED=false

cleanup() {
    local exit_code=$?

    trap - EXIT

    if [[ "$MAINTENANCE_ENABLED" == "true" ]]; then
        echo "==> Exiting maintenance mode"
        php artisan up || true
    fi

    if [[ "$exit_code" -ne 0 ]]; then
        echo "!! Deployment failed with exit code $exit_code"
        echo "!! Check application health before considering deployment complete."
    fi

    exit "$exit_code"
}

trap cleanup EXIT

echo "========================================"
echo " TMM Travels Production Deployment"
echo "========================================"

# --------------------------------------------------
# 1. Preflight checks
# --------------------------------------------------

echo "==> Checking required commands"

for cmd in git php composer npm sudo curl; do
    command -v "$cmd" >/dev/null || {
        echo "!! Missing command: $cmd"
        exit 1
    }
done

echo "==> Checking Git branch"

CURRENT_BRANCH="$(git branch --show-current)"

if [[ "$CURRENT_BRANCH" != "main" ]]; then
    echo "!! Expected main branch, found: $CURRENT_BRANCH"
    exit 1
fi

echo "==> Checking tracked Git changes"

if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "!! Tracked Git changes detected"
    git status --short
    exit 1
fi

echo "==> Fetching latest code"

git fetch origin main

echo "==> Checking fast-forward compatibility"

git merge-base --is-ancestor HEAD origin/main || {
    echo "!! Local main has diverged from origin/main"
    exit 1
}

# --------------------------------------------------
# 2. Maintenance mode
# --------------------------------------------------

echo "==> Entering maintenance mode"

php artisan down --render="errors::503" --retry=60

MAINTENANCE_ENABLED=true

# --------------------------------------------------
# 3. Update source
# --------------------------------------------------

echo "==> Updating main branch"

git merge --ff-only origin/main

# --------------------------------------------------
# 4. Composer dependencies
# --------------------------------------------------

echo "==> Installing production Composer dependencies"

composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

echo "==> Auditing Composer dependencies"

composer audit --locked

# --------------------------------------------------
# 5. Frontend dependencies
# --------------------------------------------------

echo "==> Installing npm dependencies"

npm ci

echo "==> Auditing npm dependencies"

npm audit --audit-level=high

echo "==> Building frontend assets"

npm run build

# --------------------------------------------------
# 6. Database migrations
# --------------------------------------------------

echo "==> Running database migrations"

php artisan migrate --force

# --------------------------------------------------
# 7. Optional database seeding
# --------------------------------------------------

if [[ "$RUN_SEEDERS" == "true" ]]; then
    echo "==> Running database seeders"
    php artisan db:seed --force
else
    echo "==> Skipping database seeders"
fi

# --------------------------------------------------
# 8. Optional Shield permissions
# --------------------------------------------------

if [[ "$GENERATE_SHIELD" == "true" ]]; then
    echo "==> Generating Filament Shield permissions"

    php artisan shield:generate \
        --all \
        --panel=admin \
        --ignore-existing-policies \
        --no-interaction
else
    echo "==> Skipping Shield permission generation"
fi

# --------------------------------------------------
# 9. Laravel cache optimization
# --------------------------------------------------

echo "==> Rebuilding Laravel caches"

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --------------------------------------------------
# 10. Laravel scheduler
# --------------------------------------------------

echo "==> Configuring scheduler"

SCHEDULER_CRON_TEMP="$(mktemp)"

cat > "$SCHEDULER_CRON_TEMP" <<EOF
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
* * * * * www-data cd ${APP_DIR} && /usr/bin/flock -n /tmp/wma-bot-scheduler.lock /usr/bin/php artisan schedule:run >> ${SCHEDULER_LOG} 2>&1
EOF

sudo install \
    -o root \
    -g root \
    -m 0644 \
    "$SCHEDULER_CRON_TEMP" \
    "$SCHEDULER_CRON_FILE"

rm -f "$SCHEDULER_CRON_TEMP"

sudo touch "$SCHEDULER_LOG"
sudo chown www-data:www-data "$SCHEDULER_LOG"

if systemctl list-unit-files cron.service 2>/dev/null | grep -q '^cron.service'; then
    sudo systemctl enable --now cron
elif systemctl list-unit-files crond.service 2>/dev/null | grep -q '^crond.service'; then
    sudo systemctl enable --now crond
else
    echo "!! Cron service not installed"
    exit 1
fi

echo "==> Checking scheduled tasks"

php artisan schedule:list

# --------------------------------------------------
# 11. Reload services
# --------------------------------------------------

echo "==> Testing Nginx configuration"

sudo nginx -t

echo "==> Reloading PHP-FPM"

sudo systemctl reload "$PHP_FPM_SERVICE"

echo "==> Reloading Nginx"

sudo systemctl reload nginx

# --------------------------------------------------
# 12. Restart Horizon
# --------------------------------------------------

echo "==> Restarting Horizon workers"

php artisan horizon:terminate

echo "==> Waiting for Horizon"

sleep 5

echo "==> Checking Supervisor"

STATUS="$(sudo supervisorctl status "${HORIZON_SUPERVISOR_PROGRAM}:*")"

echo "$STATUS"

if ! echo "$STATUS" | grep -q "RUNNING"; then
    echo "!! Horizon is not running"
    exit 1
fi

echo "==> Checking Horizon status"

HORIZON_STATUS="$(php artisan horizon:status)"

echo "$HORIZON_STATUS"

if ! echo "$HORIZON_STATUS" | grep -qi "running"; then
    echo "!! Horizon did not report running"
    exit 1
fi

# --------------------------------------------------
# 13. Exit maintenance mode
# --------------------------------------------------

echo "==> Bringing application online"

php artisan up

MAINTENANCE_ENABLED=false

# --------------------------------------------------
# 14. HTTP health check
# --------------------------------------------------

echo "==> Checking application health"

HTTP_CODE="$(curl \
    --silent \
    --show-error \
    --location \
    --output /dev/null \
    --write-out "%{http_code}" \
    --max-time 20 \
    https://tmmtravels.it/)"

if [[ "$HTTP_CODE" != "200" ]]; then
    echo "!! HTTP health check failed: $HTTP_CODE"
    exit 1
fi

echo "==> HTTP health check passed: $HTTP_CODE"

# --------------------------------------------------
# 15. Final verification
# --------------------------------------------------

echo "==> Laravel version"

php artisan --version

echo "==> Git commit"

git log -1 --oneline

echo "========================================"
echo " Deployment completed successfully"
echo "========================================"
