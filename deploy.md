# 1. Go to your app directory
cd /var/www/wma-app   # adjust to your actual path

# 2. Maintenance mode on
php artisan down --render="errors::503" --retry=60

# 3. Pull new code
git fetch origin
git checkout main
git pull origin main
composer install --no-dev --optimize-autoloader

# 4. Run migrations
php artisan migrate --force

# 5. Clear and rebuild Laravel caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Reload PHP-FPM (flushes OPcache so PHP sees the new files)
sudo nginx -t && sudo systemctl reload nginx
sudo systemctl reload php8.5-fpm

# 7. Zero-downtime worker restart: Horizon finishes in-flight jobs on the OLD
#    code, then exits; Supervisor (see below) immediately restarts it, picking
#    up the NEW code. Don't `pkill` Horizon — this is the graceful equivalent.
php artisan horizon:terminate

# 8. Disable maintenance mode
php artisan up

########################################
# One-time setup (new server only)
########################################

# enable shield
php artisan shield:generate --all --panel=admin --ignore-existing-policies --no-interaction

# Horizon supervises and auto-scales the actual queue workers (1..10 processes in
# production, scaling on average queue wait time — see config/horizon.php). It
# replaces running `queue:work` by hand. But Horizon itself is one long-lived
# process (`php artisan horizon`), so something still needs to keep THAT alive
# and auto-restart it if the server reboots or it crashes — that's what this
# Supervisor unit does. Install it once:
sudo tee /etc/supervisor/conf.d/wma-bot-horizon.conf > /dev/null <<'EOF'
[program:wma-bot-horizon]
process_name=%(program_name)s
command=php /var/www/wma-app/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/wma-app/storage/logs/horizon.log
stopwaitsecs=3600
EOF

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wma-bot-horizon:*

# Verify it's running:
sudo supervisorctl status wma-bot-horizon:*
php artisan horizon:status

# Dashboard (gated to the super_admin role, see HorizonServiceProvider::gate()):
#   https://your-domain/horizon
