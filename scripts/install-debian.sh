#!/bin/bash

# ============================================
# GEMBOK LARA - Debian Installation Script
# For Debian 11/12 with Nginx + MySQL/MariaDB
# ============================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${GREEN}"
echo "============================================"
echo "  GEMBOK LARA - Debian Installation"
echo "  Nginx + PHP 8.2 + MariaDB"
echo "============================================"
echo -e "${NC}"

# Configuration
APP_NAME="gembok-laravel"
APP_DIR="/var/www/${APP_NAME}"
DB_NAME="gemboklara"
DB_USER="gembok"
DB_PASS="gembok123"
DOMAIN="localhost"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root${NC}"
    exit 1
fi

# Detect Debian version
DEBIAN_VERSION=$(cat /etc/debian_version | cut -d. -f1)
echo -e "${CYAN}Detected Debian version: ${DEBIAN_VERSION}${NC}"

echo -e "${YELLOW}[1/9] Updating system...${NC}"
apt update && apt upgrade -y

echo -e "${YELLOW}[2/9] Installing dependencies...${NC}"
apt install -y curl git unzip wget gnupg2 ca-certificates lsb-release apt-transport-https

echo -e "${YELLOW}[3/9] Adding PHP repository (sury.org)...${NC}"
# Add sury.org repository for PHP 8.2 on Debian
wget -qO - https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg
echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list
apt update

echo -e "${YELLOW}[4/9] Installing PHP 8.2...${NC}"
apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
    php8.2-intl php8.2-redis php8.2-snmp php8.2-sockets

echo -e "${YELLOW}[5/9] Installing Nginx...${NC}"
# Stop and disable Apache if exists (conflicts with Nginx on port 80)
systemctl stop apache2 2>/dev/null || true
systemctl disable apache2 2>/dev/null || true
apt install -y nginx

echo -e "${YELLOW}[6/9] Installing MariaDB...${NC}"
apt install -y mariadb-server mariadb-client

# Start MariaDB
systemctl start mariadb
systemctl enable mariadb

echo -e "${YELLOW}[7/9] Creating database...${NC}"
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo -e "${YELLOW}[8/9] Installing Composer...${NC}"
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo -e "${YELLOW}[9/9] Setting up application...${NC}"

# Get current directory (where the script is run from)
CURRENT_DIR=$(pwd)

# If running from the cloned repo, use current directory
if [ -f "$CURRENT_DIR/artisan" ]; then
    echo "Using current directory as application source..."

    # Copy to APP_DIR if different
    if [ "$CURRENT_DIR" != "$APP_DIR" ]; then
        mkdir -p $APP_DIR
        cp -r $CURRENT_DIR/* $APP_DIR/
        cp -r $CURRENT_DIR/.* $APP_DIR/ 2>/dev/null || true
    fi
else
    # Clone repository
    if [ ! -d "$APP_DIR" ]; then
        echo "Cloning repository..."
        git clone https://github.com/digitallenteranusa-bot/gembok-laravel.git $APP_DIR
    fi
fi

cd $APP_DIR

# Fix git ownership warning
git config --global --add safe.directory $APP_DIR 2>/dev/null || true

# Allow composer to run as root
export COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies (use update if lock file is outdated)
echo "Installing composer dependencies..."
if ! composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null; then
    echo -e "${YELLOW}Lock file outdated, running composer update...${NC}"
    composer update --no-dev --optimize-autoloader --no-interaction
fi

# Setup environment
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Configure .env for MySQL
sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env
sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/" .env
sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/" .env
sed -i "s/# DB_PORT=3306/DB_PORT=3306/" .env
sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/# DB_USERNAME=root/DB_USERNAME=${DB_USER}/" .env
sed -i "s/# DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env

# Also handle cases where DB is already configured
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" .env
sed -i "s/^DB_PORT=.*/DB_PORT=3306/" .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env

# Generate key and run migrations
php artisan key:generate --force
php artisan storage:link --force
php artisan migrate --force
php artisan db:seed --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache

# Create Nginx config
cat > /etc/nginx/sites-available/${APP_NAME} << 'NGINX'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    root APP_DIR_PLACEHOLDER/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

# Replace placeholders
sed -i "s|APP_DIR_PLACEHOLDER|${APP_DIR}|g" /etc/nginx/sites-available/${APP_NAME}

# Enable site
ln -sf /etc/nginx/sites-available/${APP_NAME} /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and restart services
nginx -t
systemctl restart nginx
systemctl restart php8.2-fpm

# Setup cron for Laravel scheduler
(crontab -l 2>/dev/null | grep -v "${APP_DIR}"; echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo -e "${GREEN}"
echo "============================================"
echo "  Installation Complete!"
echo "============================================"
echo -e "${NC}"
echo ""
echo -e "Application URL: ${CYAN}http://${DOMAIN}${NC}"
echo -e "Admin Login: ${CYAN}http://${DOMAIN}/admin/login${NC}"
echo ""
echo "Database:"
echo "  - Name: ${DB_NAME}"
echo "  - User: ${DB_USER}"
echo "  - Pass: ${DB_PASS}"
echo ""
echo "Default Admin:"
echo "  - Email: admin@gembok.com"
echo "  - Password: password"
echo ""
echo -e "${YELLOW}Files location: ${APP_DIR}${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Access http://YOUR_SERVER_IP/admin/login"
echo "2. Configure your domain in /etc/nginx/sites-available/${APP_NAME}"
echo "3. Setup SSL with: apt install certbot python3-certbot-nginx && certbot --nginx -d yourdomain.com"
echo "4. Update .env with your production settings"
echo ""
