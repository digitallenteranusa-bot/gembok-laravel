#!/bin/bash

# ============================================
# GEMBOK LARA - Native Installation Script
# For Ubuntu 22.04/24.04 with Nginx + MySQL
# ============================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}"
echo "============================================"
echo "  GEMBOK LARA - Native Installation"
echo "  Nginx + PHP 8.2 + MySQL 8"
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
    echo -e "${RED}Please run as root (sudo)${NC}"
    exit 1
fi

echo -e "${YELLOW}[1/8] Updating system...${NC}"
apt update && apt upgrade -y

echo -e "${YELLOW}[2/8] Installing dependencies...${NC}"
apt install -y software-properties-common curl git unzip

echo -e "${YELLOW}[3/8] Installing PHP 8.2...${NC}"
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
    php8.2-intl php8.2-redis php8.2-snmp php8.2-sockets

echo -e "${YELLOW}[4/8] Installing Nginx...${NC}"
apt install -y nginx

echo -e "${YELLOW}[5/8] Installing MySQL 8...${NC}"
apt install -y mysql-server

# Start MySQL and create database
systemctl start mysql
systemctl enable mysql

echo -e "${YELLOW}[6/8] Creating database...${NC}"
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo -e "${YELLOW}[7/8] Installing Composer...${NC}"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo -e "${YELLOW}[8/8] Setting up application...${NC}"

# Clone or copy application
if [ ! -d "$APP_DIR" ]; then
    echo "Cloning repository..."
    git clone https://github.com/digitallenteranusa-bot/gembok-laravel.git $APP_DIR
fi

cd $APP_DIR

# Install dependencies
composer install --no-dev --optimize-autoloader

# Setup environment
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Configure .env
sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env
sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/" .env
sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/" .env
sed -i "s/# DB_PORT=3306/DB_PORT=3306/" .env
sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/# DB_USERNAME=root/DB_USERNAME=${DB_USER}/" .env
sed -i "s/# DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env

# Generate key and run migrations
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
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
    listen 80;
    listen [::]:80;
    server_name DOMAIN_PLACEHOLDER;
    root APP_DIR_PLACEHOLDER/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

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
sed -i "s|DOMAIN_PLACEHOLDER|${DOMAIN}|g" /etc/nginx/sites-available/${APP_NAME}
sed -i "s|APP_DIR_PLACEHOLDER|${APP_DIR}|g" /etc/nginx/sites-available/${APP_NAME}

# Enable site
ln -sf /etc/nginx/sites-available/${APP_NAME} /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and restart Nginx
nginx -t
systemctl restart nginx
systemctl restart php8.2-fpm

# Setup cron for Laravel scheduler
(crontab -l 2>/dev/null; echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo -e "${GREEN}"
echo "============================================"
echo "  Installation Complete!"
echo "============================================"
echo -e "${NC}"
echo ""
echo "Application URL: http://${DOMAIN}"
echo "Admin Login: http://${DOMAIN}/admin/login"
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
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Configure your domain in /etc/nginx/sites-available/${APP_NAME}"
echo "2. Setup SSL with: certbot --nginx -d yourdomain.com"
echo "3. Update .env with your production settings"
echo ""
