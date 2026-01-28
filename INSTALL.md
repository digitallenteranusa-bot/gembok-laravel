# 📦 Panduan Instalasi GEMBOK LARA

## Pilihan Instalasi

- [Quick Install (Script Otomatis)](#quick-install)
- [Instalasi Manual (Tanpa Docker)](#instalasi-manual)
- [Instalasi dengan Docker](#instalasi-docker)

---

## Quick Install

### Ubuntu 22.04/24.04 (Nginx + MySQL + PHP 8.2)

```bash
# Download dan jalankan script
curl -fsSL https://raw.githubusercontent.com/digitallenteranusa-bot/gembok-laravel/main/scripts/install-native.sh | sudo bash
```

Atau manual:

```bash
# Clone repo
git clone https://github.com/digitallenteranusa-bot/gembok-laravel.git
cd gembok-laravel

# Jalankan script
chmod +x scripts/install-native.sh
sudo ./scripts/install-native.sh
```

Script akan otomatis:
- Install Nginx, PHP 8.2, MySQL 8
- Buat database dan user
- Clone dan setup aplikasi
- Konfigurasi Nginx virtual host
- Setup Laravel scheduler cron

Setelah selesai, akses: `http://your-server-ip/admin/login`

---

## Instalasi Manual

### Prasyarat

- PHP >= 8.2
- Composer >= 2.0
- MySQL >= 8.0 atau MariaDB >= 10.4
- Node.js >= 18.x & NPM
- Git

### Langkah-langkah

#### 1. Clone Repository

```bash
git clone https://github.com/digitallenteranusa-bot/gembok-laravel.git
cd gembok-laravel
```

#### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

#### 3. Konfigurasi Environment

```bash
# Copy file environment
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### 4. Edit File .env

Buka file `.env` dan sesuaikan konfigurasi:

```env
APP_NAME="Arsa Net"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gembok_lara
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Mikrotik (Opsional)
MIKROTIK_ENABLED=false
MIKROTIK_HOST=192.168.1.1
MIKROTIK_PORT=8728
MIKROTIK_USERNAME=admin
MIKROTIK_PASSWORD=

# GenieACS (Opsional)
GENIEACS_URL=http://localhost:7557
GENIEACS_USERNAME=
GENIEACS_PASSWORD=

# WhatsApp Gateway (Opsional)
WHATSAPP_API_URL=http://localhost:3000
WHATSAPP_API_KEY=
WHATSAPP_SENDER=

# Payment Gateway (Opsional)
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

#### 5. Setup Database

```bash
# Buat database
mysql -u root -p -e "CREATE DATABASE gembok_lara CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Jalankan migrasi
php artisan migrate

# (Opsional) Jalankan seeder untuk data dummy
php artisan db:seed
```

#### 6. Build Assets

```bash
# Production
npm run build

# Development (dengan hot reload)
npm run dev
```

#### 7. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 8. Jalankan Aplikasi

```bash
# Development
php artisan serve

# Production - gunakan web server (Nginx/Apache)
```

---

## Instalasi Docker

### Prasyarat

- Docker >= 20.x
- Docker Compose >= 2.x

### Langkah-langkah

#### 1. Clone Repository

```bash
git clone https://github.com/digitallenteranusa-bot/gembok-laravel.git
cd gembok-laravel
```

#### 2. Konfigurasi Environment

```bash
cp .env.example .env
```

Edit `.env` untuk Docker:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=gembok_lara
DB_USERNAME=gembok
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
```

#### 3. Build & Jalankan Container

```bash
# Build dan jalankan
docker-compose up -d --build

# Lihat logs
docker-compose logs -f
```

#### 4. Setup Aplikasi

```bash
# Masuk ke container
docker-compose exec app bash

# Generate key
php artisan key:generate

# Jalankan migrasi
php artisan migrate

# (Opsional) Seed data
php artisan db:seed

# Build assets
npm install && npm run build
```

#### 5. Akses Aplikasi

- **Aplikasi**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081

### Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Rebuild containers
docker-compose up -d --build

# View logs
docker-compose logs -f app

# Execute command in container
docker-compose exec app php artisan migrate

# Clear cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
```

---

## Konfigurasi Web Server

### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/gembok-laravel/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache (.htaccess sudah include)

Pastikan `mod_rewrite` aktif:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## Akun Default

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@gembok.com | admin123 |

**⚠️ PENTING**: Segera ganti password setelah login pertama!

---

## Troubleshooting

### Permission Error

```bash
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Composer Memory Limit

```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

### Clear All Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Docker: Container tidak bisa connect ke database

Tunggu beberapa detik sampai MySQL ready, lalu:

```bash
docker-compose exec app php artisan migrate
```

---

## Scheduled Tasks & Maintenance Harian

### Setup Cron Job untuk Laravel Scheduler

Laravel Scheduler harus dijalankan setiap menit agar tugas-tugas terjadwal berjalan otomatis.

```bash
# Edit crontab
sudo crontab -e -u www-data

# Tambahkan baris ini:
* * * * * cd /var/www/gembok-laravel && php artisan schedule:run >> /dev/null 2>&1
```

### Daftar Scheduled Tasks Otomatis

Sistem sudah dikonfigurasi untuk menjalankan tugas-tugas berikut secara otomatis:

| Jadwal | Perintah | Deskripsi |
|--------|----------|-----------|
| Setiap tanggal 1, 00:01 | `billing:generate-invoices` | Generate invoice bulanan untuk semua pelanggan aktif |
| Setiap hari, 09:00 | `billing:send-reminders --days=3` | Kirim pengingat H-3 sebelum jatuh tempo |
| Setiap hari, 09:00 | `billing:send-reminders --days=1` | Kirim pengingat H-1 sebelum jatuh tempo |
| Setiap hari, 01:00 | `billing:suspend-overdue --days=7` | Suspend pelanggan yang telat 7 hari |
| Setiap hari, 02:00 | `billing:reactivate-paid` | Aktifkan kembali pelanggan yang sudah bayar |
| Setiap hari, 18:00 | `billing:report --period=daily --send` | Kirim laporan harian via WhatsApp |
| Setiap Senin, 08:00 | `billing:report --period=weekly --send` | Kirim laporan mingguan via WhatsApp |
| Setiap tanggal 1, 08:00 | `billing:report --period=monthly --send` | Kirim laporan bulanan via WhatsApp |
| Setiap jam | `mikrotik:sync-users --update` | Sync data user ke Mikrotik |
| Setiap 5 menit | `ip-monitor:check` | Cek status IP monitoring |

### Setup Supervisor untuk Queue Worker

Queue worker diperlukan untuk memproses job seperti pengiriman WhatsApp, dll.

```bash
# Install supervisor
sudo apt install -y supervisor

# Buat konfigurasi
sudo nano /etc/supervisor/conf.d/gembok-queue.conf
```

Isi file konfigurasi:
```ini
[program:gembok-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/gembok-laravel/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/gembok-laravel/storage/logs/queue.log
stopwaitsecs=3600
```

Aktifkan:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gembok-queue:*
```

---

## Perintah Artisan untuk Maintenance

### Perintah Billing

```bash
cd /var/www/gembok-laravel

# Generate invoice bulan ini
php artisan billing:generate-invoices

# Generate invoice bulan tertentu
php artisan billing:generate-invoices --month=1 --year=2024

# Kirim pengingat pembayaran (H-3 sebelum jatuh tempo)
php artisan billing:send-reminders --days=3

# Kirim pengingat pembayaran (H-1 sebelum jatuh tempo)
php artisan billing:send-reminders --days=1

# Suspend pelanggan yang telat bayar 7 hari
php artisan billing:suspend-overdue --days=7

# Preview suspend tanpa eksekusi (dry run)
php artisan billing:suspend-overdue --days=7 --dry-run

# Aktifkan kembali pelanggan yang sudah bayar
php artisan billing:reactivate-paid

# Preview reaktivasi tanpa eksekusi
php artisan billing:reactivate-paid --dry-run

# Lihat laporan billing harian
php artisan billing:report --period=daily

# Lihat laporan billing mingguan
php artisan billing:report --period=weekly

# Lihat laporan billing bulanan
php artisan billing:report --period=monthly

# Kirim laporan via WhatsApp
php artisan billing:report --period=daily --send
```

### Perintah Mikrotik

```bash
# Sync semua user ke Mikrotik (update existing)
php artisan mikrotik:sync-users --update

# Buat user baru yang belum ada di Mikrotik
php artisan mikrotik:sync-users --create

# Update dan create sekaligus
php artisan mikrotik:sync-users --update --create
```

### Perintah IP Monitoring

```bash
# Cek semua IP monitor aktif
php artisan ip-monitor:check --all

# Cek berdasarkan interval
php artisan ip-monitor:check
```

### Perintah Maintenance Aplikasi

```bash
# Clear semua cache
php artisan optimize:clear

# Rebuild cache (untuk production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# Restart queue worker
php artisan queue:restart

# Lihat daftar scheduled tasks
php artisan schedule:list

# Jalankan scheduler sekali (untuk testing)
php artisan schedule:run

# Retry semua failed jobs
php artisan queue:retry all

# Hapus semua failed jobs
php artisan queue:flush
```

### Perintah Database

```bash
# Jalankan migrasi baru
php artisan migrate --force

# Rollback migrasi terakhir
php artisan migrate:rollback

# Lihat status migrasi
php artisan migrate:status

# Fresh migrate (HATI-HATI: hapus semua data!)
php artisan migrate:fresh --seed
```

---

## Backup & Recovery

### Script Backup Otomatis

Buat script backup:
```bash
sudo nano /usr/local/bin/backup-gembok.sh
```

Isi script:
```bash
#!/bin/bash

# Konfigurasi
BACKUP_DIR="/var/backups/gembok-billing"
APP_DIR="/var/www/gembok-laravel"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Buat direktori backup
mkdir -p $BACKUP_DIR

# Backup database SQLite
if [ -f "$APP_DIR/database/database.sqlite" ]; then
    cp $APP_DIR/database/database.sqlite $BACKUP_DIR/database_$DATE.sqlite
    gzip $BACKUP_DIR/database_$DATE.sqlite
fi

# Backup database MySQL
# mysqldump -u gembok -p'password' gembok_lara > $BACKUP_DIR/database_$DATE.sql
# gzip $BACKUP_DIR/database_$DATE.sql

# Backup storage/uploads
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C $APP_DIR storage/app

# Backup .env
cp $APP_DIR/.env $BACKUP_DIR/env_$DATE.backup

# Hapus backup lama (lebih dari 30 hari)
find $BACKUP_DIR -type f -mtime +$RETENTION_DAYS -delete

# Log
echo "$(date): Backup completed" >> /var/log/gembok-backup.log
```

Set permission dan jadwalkan:
```bash
sudo chmod +x /usr/local/bin/backup-gembok.sh

# Tambahkan ke cron (jam 3 pagi setiap hari)
echo "0 3 * * * /usr/local/bin/backup-gembok.sh" | sudo tee -a /var/spool/cron/crontabs/root
```

### Restore dari Backup

```bash
# Stop queue worker
sudo supervisorctl stop gembok-queue:*

# Restore database SQLite
gunzip -c /var/backups/gembok-billing/database_YYYYMMDD_HHMMSS.sqlite.gz > /var/www/gembok-laravel/database/database.sqlite

# Restore database MySQL
# gunzip -c /var/backups/gembok-billing/database_YYYYMMDD_HHMMSS.sql.gz | mysql -u gembok -p gembok_lara

# Restore storage
tar -xzf /var/backups/gembok-billing/storage_YYYYMMDD_HHMMSS.tar.gz -C /var/www/gembok-laravel/

# Set permissions
sudo chown -R www-data:www-data /var/www/gembok-laravel/storage
sudo chown -R www-data:www-data /var/www/gembok-laravel/database

# Clear cache
cd /var/www/gembok-laravel && php artisan optimize:clear

# Start queue worker
sudo supervisorctl start gembok-queue:*
```

---

## Monitoring Log

```bash
# Log aplikasi Laravel
tail -f /var/www/gembok-laravel/storage/logs/laravel.log

# Log billing
tail -f /var/www/gembok-laravel/storage/logs/billing.log

# Log reminder
tail -f /var/www/gembok-laravel/storage/logs/reminders.log

# Log suspension
tail -f /var/www/gembok-laravel/storage/logs/suspension.log

# Log reactivation
tail -f /var/www/gembok-laravel/storage/logs/reactivation.log

# Log reports
tail -f /var/www/gembok-laravel/storage/logs/reports.log

# Log Mikrotik sync
tail -f /var/www/gembok-laravel/storage/logs/mikrotik.log

# Log IP Monitor
tail -f /var/www/gembok-laravel/storage/logs/ip-monitor.log

# Log Queue worker
tail -f /var/www/gembok-laravel/storage/logs/queue.log

# Log Nginx
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
```

---

## Cek Status Sistem

```bash
# Status services
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis
sudo supervisorctl status

# Disk usage
df -h

# Memory usage
free -h

# Process PHP
ps aux | grep php

# Test koneksi Mikrotik
php artisan tinker
>>> app(\App\Services\MikrotikService::class)->isConnected()

# Test koneksi WhatsApp
php artisan tinker
>>> app(\App\Services\WhatsAppService::class)->sendMessage('628xxx', 'Test')
```

---

## Support

Jika mengalami masalah, buka issue di:
- [GitHub Issues](https://github.com/digitallenteranusa-bot/gembok-laravel/issues)

---

## ☕ Dukung Proyek Ini

<a href="https://saweria.co/rizkylab" target="_blank">
  <img src="https://img.shields.io/badge/Saweria-Support%20Me-orange?style=for-the-badge" alt="Support via Saweria">
</a>
