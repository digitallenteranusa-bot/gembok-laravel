#!/bin/bash
# ============================================
# GEMBOK LARA - Backup Script
# ============================================
# Script untuk backup database dan storage
# Contoh cron: 0 3 * * * /var/www/gembok-laravel/scripts/backup.sh
# ============================================

set -e

# Konfigurasi - SESUAIKAN DENGAN KEBUTUHAN
APP_DIR="/var/www/gembok-laravel"
BACKUP_DIR="/var/backups/gembok-billing"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# MySQL Config (uncomment jika menggunakan MySQL)
# DB_USER="gembok"
# DB_PASS="password"
# DB_NAME="gembok_lara"

# Warna
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "============================================"
echo "GEMBOK LARA - Backup"
echo "Date: $(date)"
echo "============================================"

# Buat direktori backup
mkdir -p $BACKUP_DIR

# 1. Backup Database
echo -e "\n${YELLOW}[1/4] Backup Database...${NC}"

if [ -f "$APP_DIR/database/database.sqlite" ]; then
    # SQLite Backup
    cp "$APP_DIR/database/database.sqlite" "$BACKUP_DIR/database_$DATE.sqlite"
    gzip "$BACKUP_DIR/database_$DATE.sqlite"
    echo -e "${GREEN}✓ SQLite backup: database_$DATE.sqlite.gz${NC}"

# Uncomment untuk MySQL:
# elif [ -n "$DB_NAME" ]; then
#     # MySQL Backup
#     mysqldump -u $DB_USER -p"$DB_PASS" $DB_NAME | gzip > "$BACKUP_DIR/database_$DATE.sql.gz"
#     echo -e "${GREEN}✓ MySQL backup: database_$DATE.sql.gz${NC}"

else
    echo -e "${RED}✗ No database to backup${NC}"
fi

# 2. Backup Storage (uploads, dll)
echo -e "\n${YELLOW}[2/4] Backup Storage...${NC}"
if [ -d "$APP_DIR/storage/app" ]; then
    tar -czf "$BACKUP_DIR/storage_$DATE.tar.gz" -C "$APP_DIR" storage/app
    echo -e "${GREEN}✓ Storage backup: storage_$DATE.tar.gz${NC}"
else
    echo -e "${YELLOW}⚠ No storage/app directory${NC}"
fi

# 3. Backup .env
echo -e "\n${YELLOW}[3/4] Backup Environment...${NC}"
if [ -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env" "$BACKUP_DIR/env_$DATE.backup"
    echo -e "${GREEN}✓ Environment backup: env_$DATE.backup${NC}"
fi

# 4. Cleanup old backups
echo -e "\n${YELLOW}[4/4] Cleanup Old Backups...${NC}"
DELETED_COUNT=$(find $BACKUP_DIR -type f -mtime +$RETENTION_DAYS -delete -print | wc -l)
echo -e "${GREEN}✓ Deleted $DELETED_COUNT old files (older than $RETENTION_DAYS days)${NC}"

# Summary
echo -e "\n============================================"
echo -e "${GREEN}Backup Completed!${NC}"
echo ""
echo "Backup Location: $BACKUP_DIR"
echo ""
ls -lh $BACKUP_DIR | tail -5
echo "============================================"

# Log
echo "$(date): Backup completed - database_$DATE, storage_$DATE" >> "$APP_DIR/storage/logs/backup.log"
