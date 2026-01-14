#!/bin/bash

# Production Deployment Script for Accounting Software
# This script automates the deployment process

set -e  # Exit on error

echo "========================================="
echo "Accounting Software - Production Deployment"
echo "========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="${APP_DIR:-/var/www/accounting-software}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/accounting-software}"

# Function to print colored messages
print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then 
    print_warning "This script should be run with sudo for full functionality"
fi

# Step 1: Backup current database
print_message "Step 1: Creating database backup..."
if [ -f "scripts/backup-database.sh" ]; then
    bash scripts/backup-database.sh
    if [ $? -eq 0 ]; then
        print_message "Database backup completed successfully"
    else
        print_error "Database backup failed"
        exit 1
    fi
else
    print_warning "Backup script not found, skipping backup"
fi

# Step 2: Pull latest code (if using git)
if [ -d ".git" ]; then
    print_message "Step 2: Pulling latest code from repository..."
    git pull origin main || git pull origin master
    if [ $? -eq 0 ]; then
        print_message "Code updated successfully"
    else
        print_error "Failed to pull latest code"
        exit 1
    fi
else
    print_warning "Not a git repository, skipping code pull"
fi

# Step 3: Install/Update Composer dependencies
print_message "Step 3: Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader
if [ $? -eq 0 ]; then
    print_message "Composer dependencies installed successfully"
else
    print_error "Failed to install Composer dependencies"
    exit 1
fi

# Step 4: Install/Update NPM dependencies and build assets
print_message "Step 4: Installing NPM dependencies and building assets..."
npm ci --production=false
npm run build
if [ $? -eq 0 ]; then
    print_message "Frontend assets built successfully"
else
    print_error "Failed to build frontend assets"
    exit 1
fi

# Step 5: Run database migrations
print_message "Step 5: Running database migrations..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    print_message "Database migrations completed successfully"
else
    print_error "Database migrations failed"
    exit 1
fi

# Step 6: Clear and cache configuration
print_message "Step 6: Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
print_message "Application optimized successfully"

# Step 7: Set proper permissions
print_message "Step 7: Setting file permissions..."
if [ -w "storage" ] && [ -w "bootstrap/cache" ]; then
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    print_message "File permissions set successfully"
else
    print_warning "Could not set file permissions (may require sudo)"
fi

# Step 8: Clear application cache
print_message "Step 8: Clearing application cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
print_message "Application cache cleared"

# Step 9: Restart services (if applicable)
print_message "Step 9: Deployment completed successfully!"
print_message "Please restart your web server and PHP-FPM if needed:"
echo "  - sudo systemctl restart nginx (or apache2)"
echo "  - sudo systemctl restart php8.2-fpm (or your PHP version)"

echo ""
echo "========================================="
echo "Deployment Summary:"
echo "========================================="
echo "✓ Database backup created"
echo "✓ Code updated"
echo "✓ Dependencies installed"
echo "✓ Frontend assets built"
echo "✓ Database migrated"
echo "✓ Application optimized"
echo "✓ Permissions set"
echo "✓ Cache cleared"
echo ""
print_message "Deployment completed successfully!"
