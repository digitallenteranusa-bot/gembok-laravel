# GEMBOK Laravel - Makefile
# Usage: make <command>

.PHONY: help update install migrate fresh seed cache-clear optimize test serve

# Default target
help:
	@echo "GEMBOK Laravel - Available Commands"
	@echo "===================================="
	@echo ""
	@echo "  make update        - Update dari repository + migrasi"
	@echo "  make install       - Install fresh (composer + migrate + seed)"
	@echo "  make migrate       - Jalankan migrasi database"
	@echo "  make fresh         - Reset database + seed"
	@echo "  make seed          - Jalankan database seeder"
	@echo "  make cache-clear   - Bersihkan semua cache"
	@echo "  make optimize      - Optimasi aplikasi (cache config/route/view)"
	@echo "  make test          - Jalankan test"
	@echo "  make serve         - Jalankan development server"
	@echo "  make queue         - Jalankan queue worker"
	@echo "  make schedule      - Jalankan scheduler"
	@echo ""

# Update dari repository
update:
	@echo "Updating from repository..."
	git fetch origin
	git pull origin main
	composer install --no-interaction --prefer-dist --optimize-autoloader
	php artisan migrate --force
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache
	@echo "Update complete!"

# Fresh install
install:
	@echo "Installing application..."
	composer install
	cp -n .env.example .env || true
	php artisan key:generate --force
	php artisan migrate --force
	php artisan db:seed --force
	php artisan storage:link
	@echo "Installation complete!"

# Run migrations
migrate:
	php artisan migrate

# Fresh database
fresh:
	php artisan migrate:fresh --seed

# Run seeders
seed:
	php artisan db:seed

# Clear all cache
cache-clear:
	php artisan config:clear
	php artisan cache:clear
	php artisan view:clear
	php artisan route:clear
	@echo "All cache cleared!"

# Optimize application
optimize:
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache
	@echo "Application optimized!"

# Run tests
test:
	php artisan test

# Run development server
serve:
	php artisan serve

# Run queue worker
queue:
	php artisan queue:work --verbose --tries=3

# Run scheduler
schedule:
	php artisan schedule:work

# Sync Mikrotik (all routers)
mikrotik-sync:
	php artisan mikrotik:sync-users --router=all

# Generate invoices
generate-invoices:
	php artisan invoice:generate

# Check system status
status:
	@echo "=== System Status ==="
	@echo ""
	@echo "PHP Version:"
	@php -v | head -1
	@echo ""
	@echo "Laravel Version:"
	@php artisan --version
	@echo ""
	@echo "Git Status:"
	@git log -1 --pretty=format:"Commit: %h | Date: %ci"
	@echo ""
	@echo ""
	@echo "Database Connection:"
	@php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Connected successfully'; } catch(Exception \$$e) { echo 'Failed: ' . \$$e->getMessage(); }"
	@echo ""
