#!/bin/bash
# ============================================
# GEMBOK LARA - Weekly Maintenance Script
# ============================================
# Jalankan script ini setiap minggu untuk maintenance mendalam
# Contoh cron: 0 5 * * 0 /var/www/gembok-laravel/scripts/weekly-maintenance.sh
# ============================================

set -e

# Konfigurasi
APP_DIR="/var/www/gembok-laravel"
LOG_DIR="$APP_DIR/storage/logs"
DATE=$(date +%Y%m%d_%H%M%S)

# Warna untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "============================================"
echo "GEMBOK LARA - Weekly Maintenance"
echo "Date: $(date)"
echo "============================================"

cd $APP_DIR

# 1. Jalankan daily maintenance terlebih dahulu
echo -e "\n${YELLOW}[1/6] Running Daily Maintenance...${NC}"
if [ -f "$APP_DIR/scripts/daily-maintenance.sh" ]; then
    bash $APP_DIR/scripts/daily-maintenance.sh
fi

# 2. Clear All Cache (deep clean)
echo -e "\n${YELLOW}[2/6] Deep Cache Clean...${NC}"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Deep cache clean completed${NC}"

# 3. Clear Failed Jobs (older than 7 days)
echo -e "\n${YELLOW}[3/6] Clear Old Failed Jobs...${NC}"
php artisan queue:prune-failed --hours=168 2>/dev/null || true
echo -e "${GREEN}✓ Failed jobs cleaned${NC}"

# 4. Clear Old Notifications
echo -e "\n${YELLOW}[4/6] Cleanup Database...${NC}"
php artisan model:prune 2>/dev/null || true
echo -e "${GREEN}✓ Database cleanup completed${NC}"

# 5. Check Disk Space
echo -e "\n${YELLOW}[5/6] Disk Space Check...${NC}"
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
if [ "$DISK_USAGE" -gt 80 ]; then
    echo -e "${RED}⚠ WARNING: Disk usage is ${DISK_USAGE}%${NC}"
else
    echo -e "${GREEN}✓ Disk usage: ${DISK_USAGE}%${NC}"
fi

# 6. Generate Weekly Report
echo -e "\n${YELLOW}[6/6] Generate Weekly Report...${NC}"
php artisan billing:report --period=weekly
echo -e "${GREEN}✓ Weekly report generated${NC}"

# Summary
echo -e "\n============================================"
echo -e "${GREEN}Weekly Maintenance Completed!${NC}"
echo "Date: $(date)"
echo "============================================"

# Log maintenance
echo "$(date): Weekly maintenance completed successfully" >> $LOG_DIR/maintenance.log
