# PrepMind — Deployment Guide

Production deployment on a Linux VPS (Ubuntu 24.04 LTS). The dev environment
uses **Laravel Sail**, but Sail is a `docker-compose` wrapper aimed at local
development (permissive defaults, host-mounted code, no SSL); production runs
on **native PHP-FPM + nginx + Postgres + Redis**.

The whole guide assumes a single-host setup (≤500 concurrent users). For
horizontal scaling, the same recipe still applies — just put nginx + the
shared services on separate hosts.

---

## 1. Server prerequisites

Tested on Ubuntu 24.04 LTS, 2 vCPU / 2 GB RAM minimum.

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common ca-certificates curl gnupg lsb-release ufw fail2ban

# Firewall — only SSH + HTTP(S)
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Time sync (matters for queue + cache TTL correctness)
sudo timedatectl set-timezone Europe/Warsaw

# Swap (cheap RAM cushion for composer install + queue spikes)
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## 2. PHP 8.3 + extensions

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
  php8.3-fpm php8.3-cli \
  php8.3-pgsql php8.3-redis php8.3-bcmath php8.3-intl \
  php8.3-mbstring php8.3-xml php8.3-zip php8.3-curl \
  php8.3-gd php8.3-opcache

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- \
  --install-dir=/usr/local/bin --filename=composer
```

Tune PHP-FPM for the app (`/etc/php/8.3/fpm/pool.d/www.conf`):

```ini
pm = dynamic
pm.max_children = 20          ; ~70 MB per worker on this app → ~1.4 GB max
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 500         ; restart workers periodically to flush leaks
request_terminate_timeout = 60s
```

Enable OPcache (`/etc/php/8.3/fpm/conf.d/10-opcache.ini`):

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0   ; in prod we deploy → flush manually
opcache.save_comments=1         ; Laravel needs annotations
opcache.preload=/var/www/prepmind/current/preload.php
opcache.preload_user=www-data
```

```bash
sudo systemctl restart php8.3-fpm
```

---

## 3. PostgreSQL 16

```bash
sudo install -d /usr/share/postgresql-common/pgdg
sudo curl -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc \
  --fail https://www.postgresql.org/media/keys/ACCC4CF8.asc
echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] \
  https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" \
  | sudo tee /etc/apt/sources.list.d/pgdg.list
sudo apt update && sudo apt install -y postgresql-16

# DB + user
sudo -u postgres psql <<SQL
CREATE DATABASE prepmind;
CREATE USER prepmind WITH ENCRYPTED PASSWORD '<STRONG_RANDOM_PASSWORD>';
GRANT ALL PRIVILEGES ON DATABASE prepmind TO prepmind;
\c prepmind
GRANT ALL ON SCHEMA public TO prepmind;
SQL
```

Tighten `pg_hba.conf` so prepmind user authenticates via password over localhost only — never expose 5432 to the public internet.

---

## 4. Redis 7

```bash
sudo apt install -y redis-server
sudo sed -i 's/^supervised .*/supervised systemd/' /etc/redis/redis.conf
sudo sed -i 's/^# *requirepass .*/requirepass <STRONG_RANDOM_PASSWORD>/' /etc/redis/redis.conf
sudo systemctl enable --now redis-server
```

Redis is the cache, queue, and session store. Bind it to `127.0.0.1` only.

---

## 5. Nginx + Let's Encrypt

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
sudo systemctl enable --now nginx
```

`/etc/nginx/sites-available/prepmind`:

```nginx
server {
    listen 80;
    server_name prepmind.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name prepmind.example.com;

    ssl_certificate     /etc/letsencrypt/live/prepmind.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/prepmind.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security headers
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    root /var/www/prepmind/current/public;
    index index.php;

    client_max_body_size 8M;

    # Long cache for hashed Vite assets
    location ^~ /build/assets/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # PWA service worker must NOT be cached aggressively (or users get stale SW)
    location = /sw.js {
        add_header Cache-Control "no-cache";
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 90s;
    }

    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/prepmind /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d prepmind.example.com   # automates the cert renewal cron
```

---

## 6. App deploy (releases-style symlinks)

Layout (matches what Capistrano / Envoyer / Forge produce):

```
/var/www/prepmind/
├── releases/
│   ├── 2026-05-24-103000/   # one folder per deploy
│   └── 2026-05-23-184500/
├── shared/
│   ├── .env                 # prod secrets, mode 600, owned by www-data
│   └── storage/             # persistent across deploys
└── current → releases/2026-05-24-103000/
```

Bootstrap (run once):

```bash
sudo mkdir -p /var/www/prepmind/{releases,shared}
sudo chown -R www-data:www-data /var/www/prepmind
sudo -u www-data mkdir -p /var/www/prepmind/shared/storage/{app,framework,logs}
```

Per-release script (`/usr/local/bin/prepmind-deploy.sh`):

```bash
#!/usr/bin/env bash
set -euo pipefail

REPO="git@github.com:daniel-ciupek/AI-Powered-Tech-Interview-Prep-App.git"
ROOT="/var/www/prepmind"
RELEASE="$ROOT/releases/$(date +%Y-%m-%d-%H%M%S)"

# 1. Fetch code
sudo -u www-data git clone --depth 1 "$REPO" "$RELEASE"

# 2. Install backend dependencies (production set, no dev tools)
sudo -u www-data composer install --no-dev --prefer-dist --optimize-autoloader \
    --working-dir="$RELEASE"

# 3. Build frontend assets
sudo -u www-data bash -c "cd $RELEASE && npm ci && npm run build"

# 4. Wire up shared resources (env, storage)
sudo -u www-data ln -sf "$ROOT/shared/.env" "$RELEASE/.env"
sudo -u www-data rm -rf "$RELEASE/storage"
sudo -u www-data ln -sf "$ROOT/shared/storage" "$RELEASE/storage"

# 5. Run framework optimizations
sudo -u www-data php "$RELEASE/artisan" storage:link
sudo -u www-data php "$RELEASE/artisan" migrate --force
sudo -u www-data php "$RELEASE/artisan" config:cache
sudo -u www-data php "$RELEASE/artisan" route:cache
sudo -u www-data php "$RELEASE/artisan" view:cache
sudo -u www-data php "$RELEASE/artisan" event:cache

# 6. Atomic symlink swap — zero-downtime cutover
sudo -u www-data ln -sfn "$RELEASE" "$ROOT/current.new"
sudo -u www-data mv -Tf "$ROOT/current.new" "$ROOT/current"

# 7. Reload services so they pick up the new code path
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart prepmind-queue:*

# 8. Garbage-collect: keep last 5 releases
ls -1dt "$ROOT/releases/"*/ | tail -n +6 | sudo -u www-data xargs -r rm -rf
```

Make it executable: `sudo chmod +x /usr/local/bin/prepmind-deploy.sh`.

---

## 7. The production `.env`

Place at `/var/www/prepmind/shared/.env`, mode `600`, owner `www-data`. Start from `.env.example` (already ships prod-safe defaults), then override:

```bash
APP_NAME=PrepMind
APP_ENV=production
APP_KEY=                          # php artisan key:generate to fill once
APP_DEBUG=false
APP_URL=https://prepmind.example.com

APP_LOCALE=pl
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=prepmind
DB_USERNAME=prepmind
DB_PASSWORD=<STRONG_RANDOM_PASSWORD>

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=<STRONG_RANDOM_PASSWORD>

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=prepmind.example.com

SANCTUM_STATEFUL_DOMAINS=prepmind.example.com
```

```bash
sudo -u www-data php /var/www/prepmind/current/artisan key:generate \
  --show >> /var/www/prepmind/shared/.env
# then edit and move the value into the APP_KEY= line; remove the extra line.
sudo chmod 600 /var/www/prepmind/shared/.env
```

`SESSION_ENCRYPT=true` adds a small CPU cost but encrypts the session payload at rest — worth it because PrepMind stores BYOK Gemini keys in the DB (already `Crypt::encryptString`-ed, but the session cookie should be encrypted too).

---

## 8. Queue worker (supervisor)

`/etc/supervisor/conf.d/prepmind-queue.conf`:

```ini
[program:prepmind-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/prepmind/current/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --backoff=10
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/prepmind/shared/storage/logs/queue.log
stopwaitsecs=3600
```

```bash
sudo apt install -y supervisor
sudo systemctl enable --now supervisor
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start prepmind-queue:*
```

Why `--max-time=3600`: forces workers to recycle every hour so any memory leak from long-running jobs doesn't accumulate. The Gemini API client is the most likely culprit (large response payloads).

---

## 9. Scheduler (cron)

PrepMind currently has no scheduled jobs, but the entry is required for any future `app/Console/Kernel.php` additions (cache pruning, daily reports, etc.):

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * php /var/www/prepmind/current/artisan schedule:run >> /dev/null 2>&1
```

---

## 10. Smoke test after first deploy

```bash
curl -fsS https://prepmind.example.com/ | grep -q "PrepMind" && echo "✓ HTML"
curl -fsS https://prepmind.example.com/manifest.webmanifest | grep -q "prepmind" && echo "✓ PWA manifest"
curl -fsS -I https://prepmind.example.com/build/manifest.json | grep -q "200 OK" && echo "✓ Vite manifest"
curl -fsS -I https://prepmind.example.com/sw.js | grep -q "200 OK" && echo "✓ Service worker"
sudo -u www-data php /var/www/prepmind/current/artisan migrate:status
sudo -u www-data php /var/www/prepmind/current/artisan queue:monitor redis
```

---

## 11. Rollback (1-line, ~5 seconds)

```bash
# List previous releases
ls -1dt /var/www/prepmind/releases/

# Symlink to a previous one
sudo -u www-data ln -sfn /var/www/prepmind/releases/<TIMESTAMP> /var/www/prepmind/current.new
sudo -u www-data mv -Tf /var/www/prepmind/current.new /var/www/prepmind/current
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart prepmind-queue:*
```

If the bad release ran a forward-only migration: roll the schema back first (`artisan migrate:rollback --step=1`) **then** flip the symlink.

---

## 12. Monitoring & logs

| Source | Path |
|---|---|
| Laravel app | `/var/www/prepmind/shared/storage/logs/laravel.log` |
| Queue worker | `/var/www/prepmind/shared/storage/logs/queue.log` |
| nginx access | `/var/log/nginx/access.log` |
| nginx error | `/var/log/nginx/error.log` |
| PHP-FPM | `/var/log/php8.3-fpm.log` |
| Postgres | `/var/log/postgresql/postgresql-16-main.log` |

Recommended: ship logs to an external aggregator (Better Stack, Logtail, Papertrail) so you still have them if the VPS dies. Cheap option — `vector` or `fluent-bit` to a managed Loki.

For application-level metrics: `composer require laravel/horizon` later if you migrate the queue UI to Horizon, or `spatie/laravel-server-monitor` for sanity checks (disk, queue depth, scheduled tasks).

---

## 13. Optional: production Docker (no Sail)

If you prefer a single-host Docker setup instead of native services, write a separate `Dockerfile.prod` + `docker-compose.prod.yml` — **do not** reuse `docker-compose.yml` from Sail (it mounts the host code read-write and disables OPcache validation, which is fine in dev and catastrophic in prod). The native PHP-FPM path above is the canonical and more performant option; the Docker path mostly makes sense when you already run an orchestrator (Swarm/Kubernetes/Nomad).

---

## Appendix: secret rotation

The BYOK Gemini key is per-user (encrypted at rest with `APP_KEY`). If `APP_KEY` itself ever leaks:

1. Generate a new key (do **not** push to `current/.env` yet — keep both):
   `php artisan key:generate --show`
2. Decrypt-then-re-encrypt every user's `gemini_api_key_encrypted` column in a one-off `php artisan tinker` script using the old key for decrypt and the new key for encrypt.
3. Replace `APP_KEY` in `/var/www/prepmind/shared/.env`.
4. `php artisan config:clear && systemctl reload php8.3-fpm`.

Plan this as part of the migration, not in the middle of an outage.
