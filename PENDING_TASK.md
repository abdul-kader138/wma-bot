
1. PHP-FPM pm.max_children — I gave you the edit (5 → 15) and reload commands, but you never pasted back the php-fpm8.5 -t or reload confirmation. This is still probably sitting at 5 in production, which is a real active bottleneck under any concurrent load (webhook + admin panel competing for 5 slots).
2. Redis persistence (RDB/AOF) — this is the one I just raised and you haven't answered yet:
redis-cli info persistence | grep -E "rdb_last_save_time|rdb_changes_since_last_save|aof_enabled"
2. This matters more now than before you switched the queue, because in-flight WhatsApp messages now live only in Redis memory until a worker grabs them. If this comes back showing no AOF and infrequent RDB saves, a Redis restart could silently drop customer messages.
3. Redis maxmemory / eviction policy — also asked, not yet answered:
redis-cli info memory | grep -E "used_memory_human|maxmemory_human|maxmemory_policy"
3. If maxmemory is 0 (no cap) on a 3.7GB box that also runs MySQL, nginx, and PHP-FPM workers, Redis could theoretically grow unbounded under a traffic spike and starve the rest of the server.
4. OPcache validate_timestamps — checked locally, never confirmed on the actual Hetzner box:
php -i | grep -E "opcache.enable |opcache.validate_timestamps"
