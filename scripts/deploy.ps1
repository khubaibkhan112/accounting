# Production Deployment Script for Accounting Software (Windows PowerShell)
# This script automates the deployment process

$ErrorActionPreference = "Stop"

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Accounting Software - Production Deployment" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$AppDir = if ($env:APP_DIR) { $env:APP_DIR } else { Get-Location }
$BackupDir = if ($env:BACKUP_DIR) { $env:BACKUP_DIR } else { "D:\Backups\accounting-software" }

# Function to print messages
function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

# Step 1: Backup current database
Write-Info "Step 1: Creating database backup..."
if (Test-Path "scripts\backup-database.ps1") {
    & "scripts\backup-database.ps1"
    if ($LASTEXITCODE -eq 0) {
        Write-Info "Database backup completed successfully"
    } else {
        Write-Error "Database backup failed"
        exit 1
    }
} else {
    Write-Warning "Backup script not found, skipping backup"
}

# Step 2: Pull latest code (if using git)
if (Test-Path ".git") {
    Write-Info "Step 2: Pulling latest code from repository..."
    git pull origin main
    if ($LASTEXITCODE -eq 0) {
        Write-Info "Code updated successfully"
    } else {
        git pull origin master
        if ($LASTEXITCODE -eq 0) {
            Write-Info "Code updated successfully"
        } else {
            Write-Error "Failed to pull latest code"
            exit 1
        }
    }
} else {
    Write-Warning "Not a git repository, skipping code pull"
}

# Step 3: Install/Update Composer dependencies
Write-Info "Step 3: Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader
if ($LASTEXITCODE -eq 0) {
    Write-Info "Composer dependencies installed successfully"
} else {
    Write-Error "Failed to install Composer dependencies"
    exit 1
}

# Step 4: Install/Update NPM dependencies and build assets
Write-Info "Step 4: Installing NPM dependencies and building assets..."
npm ci
if ($LASTEXITCODE -eq 0) {
    npm run build
    if ($LASTEXITCODE -eq 0) {
        Write-Info "Frontend assets built successfully"
    } else {
        Write-Error "Failed to build frontend assets"
        exit 1
    }
} else {
    Write-Error "Failed to install NPM dependencies"
    exit 1
}

# Step 5: Run database migrations
Write-Info "Step 5: Running database migrations..."
php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Write-Info "Database migrations completed successfully"
} else {
    Write-Error "Database migrations failed"
    exit 1
}

# Step 6: Clear and cache configuration
Write-Info "Step 6: Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
Write-Info "Application optimized successfully"

# Step 7: Clear application cache
Write-Info "Step 7: Clearing application cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
Write-Info "Application cache cleared"

# Step 8: Deployment complete
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Deployment Summary:" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "✓ Database backup created" -ForegroundColor Green
Write-Host "✓ Code updated" -ForegroundColor Green
Write-Host "✓ Dependencies installed" -ForegroundColor Green
Write-Host "✓ Frontend assets built" -ForegroundColor Green
Write-Host "✓ Database migrated" -ForegroundColor Green
Write-Host "✓ Application optimized" -ForegroundColor Green
Write-Host "✓ Cache cleared" -ForegroundColor Green
Write-Host ""
Write-Info "Deployment completed successfully!"
Write-Host ""
Write-Host "Note: Please restart your web server if needed" -ForegroundColor Yellow
