#!/bin/bash

# Database Backup Script for Accounting Software
# This script creates automated backups of the database

# Configuration
BACKUP_DIR="/var/backups/accounting-software"
DB_NAME="${DB_DATABASE:-accounting_db}"
DB_USER="${DB_USERNAME:-root}"
DB_PASS="${DB_PASSWORD:-}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
RETENTION_DAYS=30

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Generate backup filename with timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql"
COMPRESSED_FILE="${BACKUP_FILE}.gz"

# Log file
LOG_FILE="$BACKUP_DIR/backup.log"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Start backup
log_message "Starting database backup..."

# Perform backup
if [ -z "$DB_PASS" ]; then
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" > "$BACKUP_FILE" 2>> "$LOG_FILE"
else
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE" 2>> "$LOG_FILE"
fi

# Check if backup was successful
if [ $? -eq 0 ]; then
    # Compress backup
    gzip "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        BACKUP_SIZE=$(du -h "$COMPRESSED_FILE" | cut -f1)
        log_message "Backup completed successfully: $COMPRESSED_FILE (Size: $BACKUP_SIZE)"
        
        # Clean up old backups (keep only last N days)
        find "$BACKUP_DIR" -name "backup_${DB_NAME}_*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete
        log_message "Cleaned up backups older than $RETENTION_DAYS days"
        
        exit 0
    else
        log_message "ERROR: Failed to compress backup file"
        exit 1
    fi
else
    log_message "ERROR: Database backup failed"
    exit 1
fi
