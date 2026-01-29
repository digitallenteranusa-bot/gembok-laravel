#!/bin/bash

echo "=========================================="
echo "  GEMBOK Laravel - Update Script"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Check if git is available
if ! command -v git &> /dev/null; then
    print_error "Git tidak ditemukan. Install Git terlebih dahulu."
    exit 1
fi

# Check if composer is available
if ! command -v composer &> /dev/null; then
    print_error "Composer tidak ditemukan. Install Composer terlebih dahulu."
    exit 1
fi

# Check if php is available
if ! command -v php &> /dev/null; then
    print_error "PHP tidak ditemukan. Install PHP terlebih dahulu."
    exit 1
fi

echo "[1/7] Mengambil update dari repository..."
if git fetch origin; then
    print_status "Fetch berhasil"
else
    print_error "Gagal fetch dari repository"
    exit 1
fi

echo ""
echo "[2/7] Menarik perubahan terbaru..."
if git pull origin main; then
    print_status "Pull berhasil"
else
    print_error "Gagal pull dari repository"
    echo "Mungkin ada konflik. Selesaikan konflik terlebih dahulu."
    exit 1
fi

echo ""
echo "[3/7] Menginstall/update dependencies Composer..."
if composer install --no-interaction --prefer-dist --optimize-autoloader; then
    print_status "Composer install berhasil"
else
    print_warning "Composer install gagal, mencoba dengan --no-scripts"
    composer install --no-interaction --prefer-dist --no-scripts
fi

echo ""
echo "[4/7] Menjalankan migrasi database..."
if php artisan migrate --force; then
    print_status "Migrasi berhasil"
else
    print_warning "Migrasi gagal. Periksa koneksi database."
fi

echo ""
echo "[5/7] Membersihkan cache..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
print_status "Cache dibersihkan"

echo ""
echo "[6/7] Mengoptimasi aplikasi..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Aplikasi dioptimasi"

echo ""
echo "[7/7] Menjalankan post-update tasks..."
php artisan storage:link 2>/dev/null || true
print_status "Post-update selesai"

echo ""
echo "=========================================="
echo -e "${GREEN}  UPDATE SELESAI!${NC}"
echo "=========================================="
echo ""
echo "Perubahan terbaru:"
git log -1 --pretty=format:"Commit: %h%nTanggal: %ci%nPesan: %s"
echo ""
echo ""
