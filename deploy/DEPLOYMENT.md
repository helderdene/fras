# HDS-FRAS Deployment Guide

> Deploy HDS-FRAS (Face Recognition Alert System) on a fresh Ubuntu 25.10 VPS (Hostinger or similar).

## Table of Contents

- [Prerequisites](#prerequisites)
- [Architecture Overview](#architecture-overview)
- [Step 1: System Update & Dependencies](#step-1-system-update--dependencies)
- [Step 2: Install Composer & Node.js](#step-2-install-composer--nodejs)
- [Step 3: MySQL Setup](#step-3-mysql-setup)
- [Step 4: Deploy Application Code](#step-4-deploy-application-code)
- [Step 5: Install Dependencies & Build](#step-5-install-dependencies--build)
- [Step 6: Production Environment](#step-6-production-environment)
- [Step 7: Run Migrations](#step-7-run-migrations)
- [Step 8: Disable SSR](#step-8-disable-ssr)
- [Step 9: Nginx Configuration](#step-9-nginx-configuration)
- [Step 10: SSL with Let's Encrypt](#step-10-ssl-with-lets-encrypt)
- [Step 11: Supervisor (Background Processes)](#step-11-supervisor-background-processes)
- [Step 12: Laravel Optimization](#step-12-laravel-optimization)
- [Step 13: Firewall](#step-13-firewall)
- [Ongoing Deployments](#ongoing-deployments)
- [Monitoring & Troubleshooting](#monitoring--troubleshooting)

---

## Prerequisites

| Requirement          | Details                                      |
| -------------------- | -------------------------------------------- |
| VPS OS               | Ubuntu 25.10                                 |
| PHP                  | 8.4                                          |
| Node.js              | 22.x                                         |
| MySQL                | 8.x                                          |
| Domain               | `fras.hdsystem.io` → `72.62.250.165` (A record) |
| SSH access           | Root or sudo user                            |
| MQTT broker          | Reachable from VPS on port 1883 (outbound)   |
| Camera subnet        | Must reach VPS for photo downloads (picURI)  |

## Architecture Overview

The production stack runs **four processes** managed by Supervisor:

```
                     ┌─────────────┐
   HTTPS (443) ──────│    Nginx    │
                     └──────┬──────┘
                            │
              ┌─────────────┼─────────────┐
              │             │             │
        PHP-FPM 8.4    WebSocket      Static
        (Laravel)      Proxy /app     Assets
              │             │
              │     ┌───────┴───────┐
              │     │  Reverb 8080  │  ← Supervisor
              │     └───────────────┘
              │
   ┌──────────┼──────────┬──────────────┐
   │          │          │              │
Queue      Scheduler   MQTT         Reverb
Worker     (cron)      Listener     WebSocket
   │          │          │              │
   └──────────┴──────────┴──────────────┘
              All managed by Supervisor
```

| Process        | Command                            | Purpose                              |
| -------------- | ---------------------------------- | ------------------------------------ |
| `hds-queue`    | `artisan queue:work`               | Process enrollment batch jobs        |
| `hds-scheduler`| `artisan schedule:work`            | Run scheduled tasks (30s/1m/daily)   |
| `hds-mqtt`     | `artisan fras:mqtt-listen`         | Receive face recognition events      |
| `hds-reverb`   | `artisan reverb:start`             | WebSocket server for real-time push  |

---

## Step 1: System Update & Dependencies

SSH into the VPS as root:

```bash
apt update && apt upgrade -y
```

Install required packages:

```bash
apt install -y \
  nginx \
  mysql-server \
  php8.4-fpm \
  php8.4-cli \
  php8.4-mysql \
  php8.4-mbstring \
  php8.4-xml \
  php8.4-curl \
  php8.4-zip \
  php8.4-gd \
  php8.4-bcmath \
  php8.4-intl \
  php8.4-readline \
  supervisor \
  unzip \
  git \
  curl \
  acl \
  certbot \
  python3-certbot-nginx
```

> **Note:** If `php8.4` packages are not in the default Ubuntu 25.10 repos, add the Ondrej PPA first:
>
> ```bash
> add-apt-repository ppa:ondrej/php -y && apt update
> ```

Verify installations:

```bash
php -v          # Should show 8.4.x
nginx -v        # Should show 1.x
mysql --version # Should show 8.x
```

---

## Step 2: Install Composer & Node.js

### Composer

```bash
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
composer --version
```

### Node.js 22

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
node -v   # Should show v22.x
npm -v    # Should show 10.x
```

---

## Step 3: MySQL Setup

Secure the installation:

```bash
mysql_secure_installation
```

Create the database and application user:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE fras CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fras'@'localhost' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON fras.* TO 'fras'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> Replace `CHANGE_ME_STRONG_PASSWORD` with a strong, unique password. You will use this in the `.env` file.

---

## Step 4: Deploy Application Code

### Option A: Git Clone (Recommended)

```bash
mkdir -p /var/www/fras
git clone https://github.com/helderdene/fras.git /var/www/fras
```

### Option B: Rsync from Local Machine

Run this **from your local machine**:

```bash
rsync -avz \
  --exclude=node_modules \
  --exclude=vendor \
  --exclude=.env \
  --exclude=storage/logs/*.log \
  --exclude=bootstrap/cache/*.php \
  /path/to/fras/ root@72.62.250.165:/var/www/fras/
```

### Set Permissions

```bash
chown -R www-data:www-data /var/www/fras
chmod -R 775 /var/www/fras/storage /var/www/fras/bootstrap/cache
```

---

## Step 5: Install Dependencies & Build

```bash
cd /var/www/fras

# PHP dependencies (production only)
composer install --no-dev --optimize-autoloader

# Frontend dependencies and build
npm ci
npm run build

# Create storage symlink
php artisan storage:link
```

---

## Step 6: Production Environment

Copy and generate the app key:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `/var/www/fras/.env` with production values:

```env
#--------------------------------------------------------------
# Application
#--------------------------------------------------------------
APP_NAME="HDS-FRAS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://fras.hdsystem.io

BCRYPT_ROUNDS=12

#--------------------------------------------------------------
# Logging
#--------------------------------------------------------------
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

#--------------------------------------------------------------
# Database
#--------------------------------------------------------------
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fras
DB_USERNAME=fras
DB_PASSWORD=CHANGE_ME_STRONG_PASSWORD

#--------------------------------------------------------------
# Session
#--------------------------------------------------------------
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_DOMAIN=fras.hdsystem.io
SESSION_SECURE_COOKIE=true

#--------------------------------------------------------------
# Drivers
#--------------------------------------------------------------
BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

#--------------------------------------------------------------
# Reverb (WebSockets)
#--------------------------------------------------------------
REVERB_APP_ID=fras-local
REVERB_APP_KEY=GENERATE_A_RANDOM_KEY
REVERB_APP_SECRET=GENERATE_A_RANDOM_SECRET
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend connects via Nginx proxy on HTTPS
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=fras.hdsystem.io
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

VITE_APP_NAME="${APP_NAME}"

#--------------------------------------------------------------
# MQTT (Camera Communication)
#--------------------------------------------------------------
MQTT_HOST=your_mqtt_broker_ip
MQTT_PORT=1883
MQTT_USERNAME=your_mqtt_username
MQTT_PASSWORD=your_mqtt_password
MQTT_CLIENT_ID=hds-fras-prod
MQTT_ENABLE_LOGGING=false

#--------------------------------------------------------------
# Mapbox
#--------------------------------------------------------------
MAPBOX_ACCESS_TOKEN=your_mapbox_access_token
MAPBOX_DARK_STYLE=your_mapbox_dark_style_url
MAPBOX_LIGHT_STYLE=your_mapbox_light_style_url

#--------------------------------------------------------------
# Mail (Mailtrap Sandbox)
#--------------------------------------------------------------
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@hdsystem.io"
MAIL_FROM_NAME="${APP_NAME}"
```

> **Generate Reverb keys** with:
>
> ```bash
> php -r "echo bin2hex(random_bytes(20)) . PHP_EOL;"  # REVERB_APP_KEY
> php -r "echo bin2hex(random_bytes(20)) . PHP_EOL;"  # REVERB_APP_SECRET
> ```

### Placeholders to Replace

| Placeholder                  | Replace With                              |
| ---------------------------- | ----------------------------------------- |
| `CHANGE_ME_STRONG_PASSWORD`  | MySQL password from Step 3                |
| `GENERATE_A_RANDOM_KEY`      | Output of random_bytes command above      |
| `GENERATE_A_RANDOM_SECRET`   | Output of random_bytes command above      |
| `your_mqtt_broker_ip`        | MQTT broker IP address                    |
| `your_mqtt_username`         | MQTT broker username                      |
| `your_mqtt_password`         | MQTT broker password                      |
| `your_mapbox_access_token`   | Mapbox public access token                |
| `your_mapbox_dark_style_url` | Mapbox dark style URL                     |
| `your_mapbox_light_style_url`| Mapbox light style URL                    |
| `your_mailtrap_username`     | Mailtrap SMTP username                    |
| `your_mailtrap_password`     | Mailtrap SMTP password                    |

---

## Step 7: Run Migrations

```bash
cd /var/www/fras
php artisan migrate --force
```

If you need seed data:

```bash
php artisan db:seed --force
```

---

## Step 8: Disable SSR

SSR is disabled for this deployment. Edit `config/inertia.php`:

```php
'ssr' => [
    'enabled' => false,
    // ...
],
```

Then build without SSR:

```bash
npm run build
```

---

## Step 9: Nginx Configuration

Create the site config:

```bash
nano /etc/nginx/sites-available/hds
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name fras.hdsystem.io;
    root /var/www/fras/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    # Max upload size (enrollment photos, face crops)
    client_max_body_size 10M;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Suppress logs for common static requests
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # PHP-FPM
    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Block dotfiles (except .well-known for SSL challenges)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Reverb WebSocket proxy
    # Nginx proxies wss://fras.hdsystem.io/app → ws://127.0.0.1:8080/app
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
        proxy_send_timeout 60s;
        proxy_pass http://127.0.0.1:8080;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
}
```

Enable the site:

```bash
ln -s /etc/nginx/sites-available/hds /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

---

## Step 10: SSL with Let's Encrypt

```bash
certbot --nginx -d fras.hdsystem.io
```

Certbot will:
- Obtain a certificate
- Modify the Nginx config to serve HTTPS
- Set up auto-renewal via systemd timer

Verify auto-renewal:

```bash
certbot renew --dry-run
```

---

## Step 11: Supervisor (Background Processes)

Copy the included supervisor configs:

```bash
cp /var/www/fras/deploy/supervisor/*.conf /etc/supervisor/conf.d/
```

This installs three process configs:
- `hds-queue.conf` — Queue worker
- `hds-reverb.conf` — WebSocket server
- `hds-mqtt.conf` — MQTT listener

Add the scheduler (not included in the repo):

```bash
cat > /etc/supervisor/conf.d/hds-scheduler.conf << 'EOF'
[program:hds-scheduler]
command=php /var/www/fras/artisan schedule:work
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/hds-scheduler.log
stopwaitsecs=10
EOF
```

Start all processes:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start all
```

Verify all are running:

```bash
supervisorctl status
```

Expected output:

```
hds-mqtt                         RUNNING   pid 12345, uptime 0:00:05
hds-queue                        RUNNING   pid 12346, uptime 0:00:05
hds-reverb                       RUNNING   pid 12347, uptime 0:00:05
hds-scheduler                    RUNNING   pid 12348, uptime 0:00:05
```

---

## Step 12: Laravel Optimization

Cache all configuration for production performance:

```bash
cd /var/www/fras

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Step 13: Firewall

```bash
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw enable
```

> **Important:** Do NOT expose port 8080 (Reverb) publicly. WebSocket traffic is proxied through Nginx on port 443 via the `/app` location block.

---

## Ongoing Deployments

Run this whenever you push updates:

```bash
cd /var/www/fras

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Run new migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart background processes
supervisorctl restart all
```

### Quick Deploy Script

You can create a deploy script at `/var/www/fras/deploy/deploy.sh`:

```bash
#!/bin/bash
set -e

cd /var/www/fras

echo "Pulling latest code..."
git pull origin main

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "Installing Node dependencies..."
npm ci

echo "Building frontend assets..."
npm run build

echo "Running migrations..."
php artisan migrate --force

echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Restarting services..."
supervisorctl restart all

echo "Deployment complete!"
```

Make it executable:

```bash
chmod +x /var/www/fras/deploy/deploy.sh
```

Run deployments with:

```bash
/var/www/fras/deploy/deploy.sh
```

---

## Monitoring & Troubleshooting

### Check Process Status

```bash
supervisorctl status
```

### View Logs

```bash
# Application log
tail -f /var/www/fras/storage/logs/laravel.log

# Supervisor process logs
tail -f /var/log/hds-queue.log
tail -f /var/log/hds-reverb.log
tail -f /var/log/hds-mqtt.log
tail -f /var/log/hds-scheduler.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### Restart Individual Processes

```bash
supervisorctl restart hds-mqtt
supervisorctl restart hds-queue
supervisorctl restart hds-reverb
supervisorctl restart hds-scheduler
```

### Common Issues

| Issue | Cause | Fix |
| ----- | ----- | --- |
| 502 Bad Gateway | PHP-FPM not running | `systemctl restart php8.4-fpm` |
| MQTT not connecting | Firewall blocking outbound 1883 | `ufw allow out 1883/tcp` |
| WebSocket failing | Reverb not running or Nginx proxy misconfigured | Check `supervisorctl status hds-reverb` and Nginx `/app` location block |
| Queue jobs stuck | Queue worker crashed | `supervisorctl restart hds-queue` |
| Storage permission denied | Ownership reset after deploy | `chown -R www-data:www-data storage bootstrap/cache` |
| "Vite manifest not found" | Frontend not built | `npm run build` |
| Stale config after deploy | Cache not cleared | `php artisan config:cache` |

### Health Check

The application exposes a health endpoint:

```bash
curl -s https://fras.hdsystem.io/up
```

### Database Backup

Add a daily backup cron:

```bash
crontab -e
```

```
0 3 * * * mysqldump -u fras -pCHANGE_ME_STRONG_PASSWORD fras | gzip > /var/backups/fras-$(date +\%Y\%m\%d).sql.gz
```

> Keep backups for at least 7 days. Prune old ones with a second cron job or logrotate.

---

## Appendix: Network Requirements

Ensure these connections are available from the VPS:

| Direction | Protocol | Port | Destination        | Purpose                     |
| --------- | -------- | ---- | ------------------ | --------------------------- |
| Outbound  | TCP      | 1883 | MQTT Broker IP     | Camera event subscription   |
| Inbound   | TCP      | 80   | `72.62.250.165`    | HTTP (redirects to HTTPS)   |
| Inbound   | TCP      | 443  | `72.62.250.165`    | HTTPS + WebSocket (wss://)  |
| Inbound   | TCP      | 22   | `72.62.250.165`    | SSH management              |
| Inbound   | HTTP     | 443  | `72.62.250.165`    | Camera photo downloads (picURI) |

> Cameras must be able to reach the VPS on HTTPS to download enrollment photos via `picURI`. Ensure the camera subnet has a route to the VPS public IP or configure the `picURI` base URL accordingly in production `.env`.
