# Gembok LARA - Quick Command Reference

Panduan cepat perintah-perintah untuk maintenance sistem billing ISP.

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

```bash
# Update user di Mikrotik
php artisan mikrotik:sync-users --update

# Buat user baru di Mikrotik
php artisan mikrotik:sync-users --create

# Update dan create sekaligus
php artisan mikrotik:sync-users --update --create
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

```bash
# Permission error
sudo chown -R www-data:www-data /var/www/gembok-laravel
sudo chmod -R 755 storage bootstrap/cache

# Clear everything
php artisan optimize:clear

# Check scheduler
php artisan schedule:list
php artisan schedule:run

# Test Mikrotik connection
php artisan tinker
>>> app(\App\Services\MikrotikService::class)->isConnected()

# Test WhatsApp
php artisan tinker
>>> app(\App\Services\WhatsAppService::class)->sendMessage('628xxx', 'Test')
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
| Setiap jam | `mikrotik:sync-users` | Sync Mikrotik |
| Setiap 5 menit | `ip-monitor:check` | Cek IP monitor |
