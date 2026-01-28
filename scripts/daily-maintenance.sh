#!/bin/bash
# ============================================
# GEMBOK LARA - Daily Maintenance Script
# ============================================
# Jalankan script ini setiap hari untuk maintenance rutin
# Contoh cron: 0 4 * * * /var/www/gembok-laravel/scripts/daily-maintenance.sh
# ============================================

set -e

# Konfigurasi
APP_DIR="/var/www/gembok-laravel"
LOG_DIR="$APP_DIR/storage/logs"
BACKUP_DIR="/var/backups/gembok-billing"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Warna untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "============================================"
echo "GEMBOK LARA - Daily Maintenance"
echo "Date: $(date)"
echo "============================================"

cd $APP_DIR

# 1. Backup Database
echo -e "\n${YELLOW}[1/7] Backup Database...${NC}"
mkdir -p $BACKUP_DIR

if [ -f "$APP_DIR/database/database.sqlite" ]; then
    cp $APP_DIR/database/database.sqlite $BACKUP_DIR/database_$DATE.sqlite
    gzip $BACKUP_DIR/database_$DATE.sqlite
    echo -e "${GREEN}✓ SQLite backup completed: database_$DATE.sqlite.gz${NC}"
else
    # MySQL backup (uncomment jika menggunakan MySQL)
    # mysqldump -u gembok -p'password' gembok_lara | gzip > $BACKUP_DIR/database_$DATE.sql.gz
    echo -e "${YELLOW}⚠ No SQLite database found, skipping backup${NC}"
fi

# 2. Backup Storage
echo -e "\n${YELLOW}[2/7] Backup Storage...${NC}"
if [ -d "$APP_DIR/storage/app" ]; then
    tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C $APP_DIR storage/app 2>/dev/null || true
    echo -e "${GREEN}✓ Storage backup completed: storage_$DATE.tar.gz${NC}"
fi

# 3. Cleanup Old Backups
echo -e "\n${YELLOW}[3/7] Cleanup Old Backups (older than $RETENTION_DAYS days)...${NC}"
DELETED=$(find $BACKUP_DIR -type f -mtime +$RETENTION_DAYS -delete -print | wc -l)
echo -e "${GREEN}✓ Deleted $DELETED old backup files${NC}"

# 4. Cleanup Old Log Files
echo -e "\n${YELLOW}[4/7] Cleanup Old Log Files...${NC}"
if [ -d "$LOG_DIR" ]; then
    # Hapus log yang lebih dari 14 hari
    find $LOG_DIR -name "*.log" -type f -mtime +14 -delete 2>/dev/null || true

    # Rotasi log Laravel jika terlalu besar (>100MB)
    if [ -f "$LOG_DIR/laravel.log" ]; then
        SIZE=$(du -m "$LOG_DIR/laravel.log" | cut -f1)
        if [ "$SIZE" -gt 100 ]; then
            mv "$LOG_DIR/laravel.log" "$LOG_DIR/laravel_$DATE.log"
            gzip "$LOG_DIR/laravel_$DATE.log"
            echo -e "${GREEN}✓ Laravel log rotated (was ${SIZE}MB)${NC}"
        fi
    fi
    echo -e "${GREEN}✓ Log cleanup completed${NC}"
fi

# 5. Clear Expired Cache & Sessions
echo -e "\n${YELLOW}[5/7] Clear Expired Cache & Sessions...${NC}"
php artisan cache:prune-stale-tags 2>/dev/null || true
php artisan auth:clear-resets 2>/dev/null || true
echo -e "${GREEN}✓ Cache & sessions cleaned${NC}"

# 6. Optimize Application
echo -e "\n${YELLOW}[6/7] Optimize Application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Application optimized${NC}"

# 7. Restart Queue Worker
echo -e "\n${YELLOW}[7/7] Restart Queue Worker...${NC}"
php artisan queue:restart
echo -e "${GREEN}✓ Queue worker restart signal sent${NC}"

# Summary
echo -e "\n============================================"
echo -e "${GREEN}Daily Maintenance Completed!${NC}"
echo "Date: $(date)"
echo "============================================"

# Log maintenance
echo "$(date): Daily maintenance completed successfully" >> $LOG_DIR/maintenance.log
