# 1. Go to your app directory
cd /var/www/wma-app   # adjust to your actual path

# 2. Maintenance mode on
php artisan down --render="errors::503" --retry=60

# 3. Stop the queue worker BEFORE pulling new code
#    (old worker is still running old job classes — let it stop cleanly)
pkill -f "queue:work"

# 4. Pull new code
git fetch origin
git checkout main
git pull origin main
composer install --no-dev --optimize-autoloader

# 5. Run migrations
php artisan migrate --force

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

# 8. Start the queue worker again (new code, fresh process)
nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 > storage/logs/worker.log 2>&1 &
disown

# 9. Confirm the worker is actually running
ps aux | grep "queue:work" | grep -v grep


########################################

# to run multiple worker
# Stop whatever's currently running (if any)
pkill -f "queue:work"

# Start 4 worker processes in parallel
for i in 1 2 3 4; do
  nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker.log 2>&1 &
  disown
done

Verify all 4 are actually running:
ps aux | grep "queue:work" | grep -v grep
You should see 4 separate PIDs.

After every deploy going forward

Same pattern as before, just repeated 4 times instead of once:
pkill -f "queue:work"
for i in 1 2 3 4; do
  nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker.log 2>&1 &
  disown
done
