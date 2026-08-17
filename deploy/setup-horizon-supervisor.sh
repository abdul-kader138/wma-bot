#!/usr/bin/env bash
#
# One-time (per-server) setup: installs Supervisor if missing and configures
# it to keep `php artisan horizon` running. Safe to re-run — reinstalls the
# same idempotent config and (re)starts the program if it isn't already up.
#
# After this runs successfully once, ordinary deploys go through deploy.sh,
# which just gracefully restarts Horizon via `horizon:terminate` and expects
# Supervisor to already be here to bring it back.
#
# Usage: sudo ./deploy/setup-horizon-supervisor.sh

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HORIZON_SUPERVISOR_PROGRAM="wma-bot-horizon"
SUPERVISOR_CONF="/etc/supervisor/conf.d/${HORIZON_SUPERVISOR_PROGRAM}.conf"

if [[ $EUID -ne 0 ]]; then
    echo "!! Run this as root (or with sudo)." >&2
    exit 1
fi

# Same detection deploy.md tells you to do by hand — whichever user actually
# owns the running php-fpm pool workers is the user Horizon should run as,
# so file permissions on storage/, bootstrap/cache/, etc. stay consistent.
FPM_USER=$(ps -eo user,comm | awk '$2 == "php-fpm:" {print $1; exit}')
if [[ -z "$FPM_USER" ]]; then
    FPM_USER=$(ps aux | grep "php-fpm: pool" | grep -v grep | awk '{print $1}' | head -1)
fi
if [[ -z "$FPM_USER" ]]; then
    echo "!! Could not detect the php-fpm pool user automatically. Falling back to www-data."
    echo "   Verify with: ps aux | grep 'php-fpm: pool' | grep -v grep"
    FPM_USER="www-data"
fi
echo "==> Using '${FPM_USER}' as the Horizon process user (detected from php-fpm)"

if ! command -v supervisorctl >/dev/null 2>&1; then
    echo "==> Installing Supervisor"
    apt-get update
    apt-get install -y supervisor
else
    echo "==> Supervisor already installed"
fi

echo "==> Writing ${SUPERVISOR_CONF}"
cat > "$SUPERVISOR_CONF" <<EOF
[program:${HORIZON_SUPERVISOR_PROGRAM}]
process_name=%(program_name)s
command=php ${APP_DIR}/artisan horizon
directory=${APP_DIR}
autostart=true
autorestart=true
user=${FPM_USER}
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/horizon.log
stopwaitsecs=3600
EOF

echo "==> Enabling and starting the supervisor service"
systemctl enable --now supervisor

echo "==> Reloading Supervisor config"
supervisorctl reread
supervisorctl update
supervisorctl start "${HORIZON_SUPERVISOR_PROGRAM}:*" || true

echo "==> Waiting for Horizon to come up"
sleep 5

echo "==> Verifying"
STATUS=$(supervisorctl status "${HORIZON_SUPERVISOR_PROGRAM}:*")
echo "$STATUS"
if ! echo "$STATUS" | grep -q RUNNING; then
    echo "!! Horizon did not come up under Supervisor — check ${APP_DIR}/storage/logs/horizon.log"
    exit 1
fi

cd "$APP_DIR"
HORIZON_STATUS=$(sudo -u "$FPM_USER" php artisan horizon:status)
echo "$HORIZON_STATUS"
if ! echo "$HORIZON_STATUS" | grep -qi "running"; then
    echo "!! horizon:status did not report running"
    exit 1
fi

echo "==> Setup complete. Future deploys can go through ./deploy.sh as normal."
