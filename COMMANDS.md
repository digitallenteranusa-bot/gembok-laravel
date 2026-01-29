# Gembok LARA - Command Reference

Panduan lengkap perintah-perintah untuk maintenance sistem billing ISP.

---

## Daftar Isi

- [Quick Update](#quick-update)
- [Makefile Commands](#makefile-commands)
- [Setup Cron](#setup-cron-wajib-untuk-production)
- [Billing Commands](#billing-commands)
- [Mikrotik Commands](#mikrotik-commands)
- [Collector Commands](#collector-commands)
- [IP Monitoring Commands](#ip-monitoring-commands)
- [Queue & Cache Commands](#queue--cache-commands)
- [Database Commands](#database-commands)
- [Git Commands](#git-commands)
- [Maintenance Scripts](#maintenance-scripts)
- [Log Monitoring](#log-monitoring)
- [Service Management](#service-management)
- [Troubleshooting](#troubleshooting)
- [Jadwal Otomatis](#jadwal-otomatis-scheduler)

---

## Quick Update

### Windows
```batch
# Double-click atau jalankan di Command Prompt:
update.bat
```

### Linux / Mac
```bash
# Beri permission (sekali saja):
chmod +x update.sh

# Jalankan update:
./update.sh
```

### Menggunakan Make
```bash
make update
```

Script update akan otomatis:
1. ✅ Fetch & pull dari repository
2. ✅ Install/update composer dependencies
3. ✅ Jalankan migrasi database
4. ✅ Clear cache lama
5. ✅ Optimize aplikasi

---

## Makefile Commands

Gunakan perintah `make <command>` di terminal.

### Perintah Utama

| Perintah | Deskripsi |
|----------|-----------|
| `make help` | Tampilkan daftar perintah |
| `make update` | Update dari repo + migrasi + optimize |
| `make install` | Install fresh (composer + migrate + seed) |
| `make status` | Cek status sistem |

### Database

| Perintah | Deskripsi |
|----------|-----------|
| `make migrate` | Jalankan migrasi database |
| `make fresh` | Reset database + seed ulang |
| `make seed` | Jalankan database seeder |

### Cache & Optimasi

| Perintah | Deskripsi |
|----------|-----------|
| `make cache-clear` | Bersihkan semua cache |
| `make optimize` | Cache config/route/view untuk production |

### Development & Background Jobs

| Perintah | Deskripsi |
|----------|-----------|
| `make serve` | Jalankan development server |
| `make queue` | Jalankan queue worker |
| `make schedule` | Jalankan task scheduler |
| `make test` | Jalankan automated tests |

### Mikrotik

| Perintah | Deskripsi |
|----------|-----------|
| `make mikrotik-sync` | Sync users dari semua router |

---

## Setup Cron (WAJIB untuk Production)

```bash
# Edit crontab
sudo crontab -e -u www-data

# Tambahkan baris berikut:
* * * * * cd /var/www/gembok-laravel && php artisan schedule:run >> /dev/null 2>&1
0 3 * * * /var/www/gembok-laravel/scripts/backup.sh >> /var/log/gembok-backup.log 2>&1
0 4 * * * /var/www/gembok-laravel/scripts/daily-maintenance.sh >> /var/log/gembok-maintenance.log 2>&1
0 5 * * 0 /var/www/gembok-laravel/scripts/weekly-maintenance.sh >> /var/log/gembok-maintenance.log 2>&1
```

---

## Billing Commands

```bash
# Generate invoice bulanan (semua pelanggan aktif)
php artisan billing:generate-invoices

# Generate invoice bulan tertentu
php artisan billing:generate-invoices --month=1 --year=2025

# Kirim pengingat pembayaran (H-3)
php artisan billing:send-reminders --days=3

# Kirim pengingat pembayaran (H-1)
php artisan billing:send-reminders --days=1

# Suspend pelanggan yang telat 7 hari
php artisan billing:suspend-overdue --days=7

# Preview suspend (tanpa eksekusi)
php artisan billing:suspend-overdue --days=7 --dry-run

# Reaktivasi pelanggan yang sudah bayar
php artisan billing:reactivate-paid

# Preview reaktivasi
php artisan billing:reactivate-paid --dry-run

# Lihat laporan billing
php artisan billing:report --period=daily
php artisan billing:report --period=weekly
php artisan billing:report --period=monthly

# Kirim laporan via WhatsApp
php artisan billing:report --period=daily --send
```

---

## Mikrotik Commands

### Multi-Router Sync

```bash
# Sync dari SEMUA router
php artisan mikrotik:sync-users --router=all

# Sync dari router tertentu (by ID)
php artisan mikrotik:sync-users --router=1
php artisan mikrotik:sync-users --router=2

# Sync dari default router saja
php artisan mikrotik:sync-users
```

### Legacy Commands

```bash
# Update user di Mikrotik
php artisan mikrotik:sync-users --update

# Buat user baru di Mikrotik
php artisan mikrotik:sync-users --create

# Update dan create sekaligus
php artisan mikrotik:sync-users --update --create
```

### Router Management (via Admin Panel)

```
URL: /admin/mikrotik/routers

Fitur:
- Tambah router baru
- Edit konfigurasi router
- Test koneksi per router
- Set default router
- Hapus router
```

---

## Collector Commands

### Admin Panel

```
Collector Management: /admin/collectors
- Daftar semua collector
- Tambah/edit collector
- Lihat detail & statistik
- Laporan per collector

Laporan Collector: /admin/collectors/{id}/report
- Filter berdasarkan periode
- Daftar pelanggan & hutang
- Riwayat pembayaran
- Export ke Excel

Export Excel: /admin/collectors/{id}/export
- Download laporan lengkap
- Format: .xlsx
```

### Assign Collector ke Pelanggan

```
Customer Create/Edit: /admin/customers/create atau /admin/customers/{id}/edit
- Pilih collector dari dropdown
- Collector hanya bisa melihat pelanggan yang di-assign
```

---

## IP Monitoring Commands

```bash
# Cek semua IP monitor
php artisan ip-monitor:check --all

# Cek berdasarkan interval
php artisan ip-monitor:check
```

---

## Queue & Cache Commands

```bash
# Restart queue worker
php artisan queue:restart

# Lihat queue status
php artisan queue:monitor

# Retry failed jobs
php artisan queue:retry all

# Hapus failed jobs
php artisan queue:flush

# Clear semua cache
php artisan optimize:clear

# Rebuild cache (production)
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## Database Commands

```bash
# Jalankan migrasi
php artisan migrate --force

# Rollback migrasi terakhir
php artisan migrate:rollback

# Status migrasi
php artisan migrate:status

# Reset database (HATI-HATI!)
php artisan migrate:fresh

# Reset + seed
php artisan migrate:fresh --seed

# Jalankan seeder saja
php artisan db:seed
```

---

## Git Commands

### Update dari Repository

```bash
# Quick update (recommended)
git pull origin main

# Fetch dulu lalu pull
git fetch origin
git pull origin main

# Pull dengan rebase
git pull --rebase origin main
```

### Cek Status

```bash
# Status file
git status

# Log commit terakhir
git log --oneline -10

# Lihat perubahan
git diff
```

### Commit & Push

```bash
# Stage semua
git add .

# Commit
git commit -m "pesan commit"

# Push
git push origin main
```

### Branching

```bash
# Buat branch baru
git checkout -b nama-branch

# Pindah branch
git checkout nama-branch

# Merge ke main
git checkout main
git merge nama-branch
```

---

## Maintenance Scripts

```bash
# Set permission dulu
chmod +x scripts/*.sh

# Health check
./scripts/health-check.sh

# Backup manual
./scripts/backup.sh

# Daily maintenance
./scripts/daily-maintenance.sh

# Weekly maintenance
./scripts/weekly-maintenance.sh
```

---

## Log Monitoring

```bash
# Log aplikasi
tail -f storage/logs/laravel.log

# Log billing
tail -f storage/logs/billing.log

# Log reminder
tail -f storage/logs/reminders.log

# Log suspension
tail -f storage/logs/suspension.log

# Log Mikrotik
tail -f storage/logs/mikrotik.log

# Log IP Monitor
tail -f storage/logs/ip-monitor.log

# Semua log (combined)
tail -f storage/logs/*.log
```

---

## Service Management

```bash
# Nginx
sudo systemctl start|stop|restart|status nginx

# PHP-FPM
sudo systemctl start|stop|restart|status php8.2-fpm

# MySQL
sudo systemctl start|stop|restart|status mysql

# Redis
sudo systemctl start|stop|restart|status redis

# Supervisor (Queue Worker)
sudo supervisorctl status
sudo supervisorctl restart gembok-queue:*
```

---

## Troubleshooting

### Permission Error
```bash
sudo chown -R www-data:www-data /var/www/gembok-laravel
sudo chmod -R 755 storage bootstrap/cache
```

### Clear Everything
```bash
php artisan optimize:clear
composer dump-autoload
```

### Check Scheduler
```bash
php artisan schedule:list
php artisan schedule:run
```

### Test Mikrotik Connection
```bash
php artisan tinker
>>> app(\App\Services\MikrotikServiceFactory::class)->default()->isConnected()

# Atau test router tertentu
>>> app(\App\Services\MikrotikServiceFactory::class)->forRouterId(1)->isConnected()
```

### Test WhatsApp
```bash
php artisan tinker
>>> app(\App\Services\WhatsAppService::class)->sendMessage('628xxx', 'Test')
```

### Database Connection Error
```bash
# Cek .env file
# Pastikan DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD benar

php artisan tinker
>>> DB::connection()->getPdo();
```

### Class Not Found
```bash
composer dump-autoload
php artisan cache:clear
php artisan config:clear
```

---

## Jadwal Otomatis (Scheduler)

| Waktu | Perintah | Keterangan |
|-------|----------|------------|
| Tgl 1, 00:01 | `billing:generate-invoices` | Generate invoice bulanan |
| Setiap hari, 09:00 | `billing:send-reminders --days=3` | Pengingat H-3 |
| Setiap hari, 09:00 | `billing:send-reminders --days=1` | Pengingat H-1 |
| Setiap hari, 01:00 | `billing:suspend-overdue` | Suspend telat 7 hari |
| Setiap hari, 02:00 | `billing:reactivate-paid` | Reaktivasi yang bayar |
| Setiap hari, 18:00 | `billing:report --daily` | Laporan harian |
| Senin, 08:00 | `billing:report --weekly` | Laporan mingguan |
| Tgl 1, 08:00 | `billing:report --monthly` | Laporan bulanan |
| Setiap jam | `mikrotik:sync-users --router=all` | Sync semua router |
| Setiap 5 menit | `ip-monitor:check` | Cek IP monitor |

---

## Environment Variables

File `.env` penting:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gembok
DB_USERNAME=root
DB_PASSWORD=secret

# Queue
QUEUE_CONNECTION=database

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
```

---

## Supervisor Config (Queue Worker)

```ini
[program:gembok-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/gembok-laravel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/gembok-laravel/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gembok-worker:*
```

---

## Quick Reference Card

```bash
# === DAILY OPERATIONS ===
make update          # Update project
make serve           # Run dev server
make status          # Check system

# === DATABASE ===
make migrate         # Run migrations
make fresh           # Reset + seed
make cache-clear     # Clear all cache

# === MIKROTIK ===
make mikrotik-sync   # Sync all routers

# === MANUAL UPDATE ===
git pull origin main && composer install && php artisan migrate --force && php artisan optimize
```

---

*GEMBOK Laravel ISP Billing System - Documentation*
