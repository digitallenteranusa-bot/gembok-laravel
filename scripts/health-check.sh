#!/bin/bash
# ============================================
# GEMBOK LARA - Health Check Script
# ============================================
# Script untuk cek kesehatan sistem
# Jalankan: ./scripts/health-check.sh
# ============================================

# Konfigurasi
APP_DIR="/var/www/gembok-laravel"
APP_URL="http://localhost"  # Ganti dengan URL aplikasi

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "============================================"
echo "GEMBOK LARA - Health Check"
echo "Date: $(date)"
echo "============================================"

ERRORS=0

# 1. Check PHP
echo -e "\n${YELLOW}[1/10] PHP Version...${NC}"
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1)
    echo -e "${GREEN}✓ $PHP_VERSION${NC}"
else
    echo -e "${RED}✗ PHP not found${NC}"
    ((ERRORS++))
fi

# 2. Check Composer
echo -e "\n${YELLOW}[2/10] Composer...${NC}"
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | head -n 1)
    echo -e "${GREEN}✓ $COMPOSER_VERSION${NC}"
else
    echo -e "${RED}✗ Composer not found${NC}"
    ((ERRORS++))
fi

# 3. Check Node.js
echo -e "\n${YELLOW}[3/10] Node.js...${NC}"
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v)
    echo -e "${GREEN}✓ Node.js $NODE_VERSION${NC}"
else
    echo -e "${RED}✗ Node.js not found${NC}"
    ((ERRORS++))
fi

# 4. Check Nginx
echo -e "\n${YELLOW}[4/10] Nginx...${NC}"
if systemctl is-active --quiet nginx 2>/dev/null; then
    echo -e "${GREEN}✓ Nginx is running${NC}"
else
    echo -e "${RED}✗ Nginx is not running${NC}"
    ((ERRORS++))
fi

# 5. Check PHP-FPM
echo -e "\n${YELLOW}[5/10] PHP-FPM...${NC}"
if systemctl is-active --quiet php8.2-fpm 2>/dev/null || systemctl is-active --quiet php-fpm 2>/dev/null; then
    echo -e "${GREEN}✓ PHP-FPM is running${NC}"
else
    echo -e "${YELLOW}⚠ PHP-FPM status unknown (might be using different version)${NC}"
fi

# 6. Check MySQL/SQLite
echo -e "\n${YELLOW}[6/10] Database...${NC}"
cd $APP_DIR 2>/dev/null
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | grep -q "OK"; then
    echo -e "${GREEN}✓ Database connection OK${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
    ((ERRORS++))
fi

# 7. Check Redis (jika digunakan)
echo -e "\n${YELLOW}[7/10] Redis (optional)...${NC}"
if command -v redis-cli &> /dev/null; then
    if redis-cli ping 2>/dev/null | grep -q "PONG"; then
        echo -e "${GREEN}✓ Redis is running${NC}"
    else
        echo -e "${YELLOW}⚠ Redis is not responding${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Redis not installed (optional)${NC}"
fi

# 8. Check Supervisor/Queue
echo -e "\n${YELLOW}[8/10] Queue Worker (Supervisor)...${NC}"
if command -v supervisorctl &> /dev/null; then
    QUEUE_STATUS=$(supervisorctl status gembok-queue:* 2>/dev/null | grep -c "RUNNING" || echo "0")
    if [ "$QUEUE_STATUS" -gt 0 ]; then
        echo -e "${GREEN}✓ $QUEUE_STATUS queue worker(s) running${NC}"
    else
        echo -e "${YELLOW}⚠ No queue workers running (check supervisor)${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Supervisor not installed${NC}"
fi

# 9. Check Disk Space
echo -e "\n${YELLOW}[9/10] Disk Space...${NC}"
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
DISK_AVAIL=$(df -h / | awk 'NR==2 {print $4}')
if [ "$DISK_USAGE" -lt 80 ]; then
    echo -e "${GREEN}✓ Disk usage: ${DISK_USAGE}% (${DISK_AVAIL} available)${NC}"
elif [ "$DISK_USAGE" -lt 90 ]; then
    echo -e "${YELLOW}⚠ Disk usage: ${DISK_USAGE}% (${DISK_AVAIL} available) - Consider cleanup${NC}"
else
    echo -e "${RED}✗ Disk usage: ${DISK_USAGE}% - Critical!${NC}"
    ((ERRORS++))
fi

# 10. Check Memory
echo -e "\n${YELLOW}[10/10] Memory...${NC}"
MEM_TOTAL=$(free -h | awk '/^Mem:/ {print $2}')
MEM_USED=$(free -h | awk '/^Mem:/ {print $3}')
MEM_PERCENT=$(free | awk '/^Mem:/ {printf "%.0f", $3/$2 * 100}')
if [ "$MEM_PERCENT" -lt 80 ]; then
    echo -e "${GREEN}✓ Memory: ${MEM_USED} / ${MEM_TOTAL} (${MEM_PERCENT}%)${NC}"
elif [ "$MEM_PERCENT" -lt 90 ]; then
    echo -e "${YELLOW}⚠ Memory: ${MEM_USED} / ${MEM_TOTAL} (${MEM_PERCENT}%)${NC}"
else
    echo -e "${RED}✗ Memory: ${MEM_USED} / ${MEM_TOTAL} (${MEM_PERCENT}%) - Critical!${NC}"
    ((ERRORS++))
fi

# Summary
echo -e "\n============================================"
if [ "$ERRORS" -eq 0 ]; then
    echo -e "${GREEN}Health Check: ALL OK${NC}"
else
    echo -e "${RED}Health Check: $ERRORS ISSUE(S) FOUND${NC}"
fi
echo "============================================"

exit $ERRORS
