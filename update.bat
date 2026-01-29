@echo off
echo ==========================================
echo   GEMBOK Laravel - Update Script
echo ==========================================
echo.

:: Check if git is available
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Git tidak ditemukan. Install Git terlebih dahulu.
    pause
    exit /b 1
)

:: Check if composer is available
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Composer tidak ditemukan. Install Composer terlebih dahulu.
    pause
    exit /b 1
)

echo [1/7] Mengambil update dari repository...
git fetch origin
if %errorlevel% neq 0 (
    echo [ERROR] Gagal fetch dari repository
    pause
    exit /b 1
)

echo.
echo [2/7] Menarik perubahan terbaru...
git pull origin main
if %errorlevel% neq 0 (
    echo [ERROR] Gagal pull dari repository
    echo Mungkin ada konflik. Selesaikan konflik terlebih dahulu.
    pause
    exit /b 1
)

echo.
echo [3/7] Menginstall/update dependencies Composer...
composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo [WARNING] Composer install gagal, mencoba dengan --no-scripts
    composer install --no-interaction --prefer-dist --no-scripts
)

echo.
echo [4/7] Menjalankan migrasi database...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo [WARNING] Migrasi gagal. Periksa koneksi database.
)

echo.
echo [5/7] Membersihkan cache...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo.
echo [6/7] Mengoptimasi aplikasi...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo.
echo [7/7] Menjalankan post-update tasks...
php artisan storage:link >nul 2>&1

echo.
echo ==========================================
echo   UPDATE SELESAI!
echo ==========================================
echo.
echo Perubahan terbaru:
git log -1 --pretty=format:"Commit: %%h%%nTanggal: %%ci%%nPesan: %%s" 2>nul
echo.
echo.
pause
