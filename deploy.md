# Deploying (run this after every `git pull`)

This same repository is deployed to more than one server, each checked out to
its own directory (currently `/var/www/tmmtravels` and
`/var/www/virtual-assistant`). `deploy.sh` detects its own location from
where it's checked out, so the identical file works unmodified on every
server — never hardcode a path into it.

All of the steps below are automated in `deploy.sh` — from the app directory,
just run:
```bash
./deploy.sh
```
It's safe to re-run (idempotent migrations/seeders), always turns maintenance
mode back off even if a step fails, and exits non-zero with a clear message
if Horizon doesn't come back up cleanly. The manual steps below are the same
thing spelled out, for reference or if you need to run part of it by hand —
replace `/var/www/tmmtravels` with the actual app directory on that server.

```bash
# 1. Go to the app directory
cd /var/www/tmmtravels

# 2. Maintenance mode on
php artisan down --render="errors::503" --retry=60

# 3. Pull new code
git fetch origin
git checkout main
git pull origin main
composer install --no-dev --optimize-autoloader

# 4. Run migrations
php artisan migrate --force

# 5. Re-seed defaults — safe to run every time: every seeder uses
#    updateOrCreate(), so it only fills in settings/services/roles that are
#    missing or new, and never overwrites anything you've since customized
#    through the admin panel.
php artisan db:seed --force

# 5b. Regenerate Shield permissions — safe/idempotent, and required on every
#     server. Without it the `permissions` table has no row for a given
#     resource/page/widget, and NOBODY can see that resource in the panel —
#     not even super_admin, since Shield checks the permission record, not
#     just the role. This is the #1 cause of "I pulled the same code onto a
#     new server and some pages are just missing from the menu."
php artisan shield:generate --all --panel=admin --ignore-existing-policies --no-interaction

# 6. Clear and rebuild Laravel caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Reload PHP-FPM (flushes OPcache so PHP sees the new files)
sudo nginx -t && sudo systemctl reload nginx
sudo systemctl reload php8.5-fpm

# 8. Zero-downtime worker restart: Horizon finishes in-flight jobs on the OLD
#    code, then exits; Supervisor immediately restarts it, picking up the NEW
#    code. Don't `pkill` Horizon — this is the graceful equivalent.
php artisan horizon:terminate

# 9. Disable maintenance mode
php artisan up

# 10. Verify everything actually came back up
sudo supervisorctl status wma-bot-horizon:*   # expect: RUNNING
php artisan horizon:status                     # expect: "Horizon is running."
php artisan schedule:list
sudo systemctl is-active cron                   # expect: active
sudo cat /etc/cron.d/wma-bot-scheduler
```

`deploy.sh` idempotently installs the single Laravel scheduler entry at
`/etc/cron.d/wma-bot-scheduler`. Do not add separate cron entries for individual
Maria workflows. Scheduler output is written to
`/var/www/tmmtravels/storage/logs/scheduler.log`.

`shield:generate` (step 5b / part of `deploy.sh`) only assigns newly generated
permissions to `super_admin`. If you've customized what `panel_user` or
`operator` can see via the Shield Roles screen in the admin panel, those
custom assignments are untouched by a re-run — you don't need to redo them
after every deploy.

---

# One-time setup (new server only — already done on this server)

Run this once per server, substituting that server's own app directory
everywhere `/var/www/tmmtravels` appears below (e.g. `/var/www/virtual-assistant`
on the other server) and giving the Supervisor program a name unique to that
app if it ever shares a box with another instance — `wma-bot-horizon` as
written assumes one instance per server, which is the current setup.

Horizon supervises and auto-scales the actual queue workers (1..10 processes
in production, scaling on average queue wait time — see `config/horizon.php`).
It replaces running `queue:work` by hand. But Horizon itself is one
long-lived process (`php artisan horizon`), so something still needs to keep
THAT alive and auto-restart it if the server reboots or it crashes — that's
what this Supervisor unit does.

```bash
# Confirm the user PHP-FPM/nginx actually runs as before using it below:
#   ps aux | grep "php-fpm: pool" | grep -v grep
# (on this server, that's www-data)

sudo tee /etc/supervisor/conf.d/wma-bot-horizon.conf > /dev/null <<'EOF'
[program:wma-bot-horizon]
process_name=%(program_name)s
command=php /var/www/tmmtravels/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/tmmtravels/storage/logs/horizon.log
stopwaitsecs=3600
EOF

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wma-bot-horizon:*

# Verify it's running:
sudo supervisorctl status wma-bot-horizon:*
php artisan horizon:status
```

Dashboard (gated to the `super_admin` role, see `HorizonServiceProvider::gate()`):
`https://your-domain/horizon`

If you're migrating this server away from the old manual-worker approach,
stop those first so they don't double-process jobs alongside Horizon:
```bash
pkill -f "queue:work"
```
