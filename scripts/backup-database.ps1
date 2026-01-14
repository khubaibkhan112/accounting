# Database Backup Script for Accounting Software (Windows PowerShell)
# This script creates automated backups of the database

# Configuration
$BackupDir = "D:\Backups\accounting-software"
$DbName = $env:DB_DATABASE
if (-not $DbName) { $DbName = "accounting_db" }
$DbUser = $env:DB_USERNAME
if (-not $DbUser) { $DbUser = "root" }
$DbPass = $env:DB_PASSWORD
$DbHost = $env:DB_HOST
if (-not $DbHost) { $DbHost = "127.0.0.1" }
$DbPort = $env:DB_PORT
if (-not $DbPort) { $DbPort = "3306" }
$RetentionDays = 30

# Create backup directory if it doesn't exist
if (-not (Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
}

# Generate backup filename with timestamp
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$BackupFile = Join-Path $BackupDir "backup_${DbName}_${Timestamp}.sql"
$CompressedFile = "${BackupFile}.gz"

# Log file
$LogFile = Join-Path $BackupDir "backup.log"

# Function to log messages
function Log-Message {
    param([string]$Message)
    $LogEntry = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] $Message"
    Add-Content -Path $LogFile -Value $LogEntry
    Write-Host $LogEntry
}

# Start backup
Log-Message "Starting database backup..."

# Build mysqldump command
$MySqlDumpPath = "mysqldump"
$MySqlDumpArgs = @(
    "-h", $DbHost,
    "-P", $DbPort,
    "-u", $DbUser
)

if ($DbPass) {
    $MySqlDumpArgs += "-p$DbPass"
}

$MySqlDumpArgs += $DbName

# Perform backup
try {
    & $MySqlDumpPath $MySqlDumpArgs | Out-File -FilePath $BackupFile -Encoding UTF8
    
    if ($LASTEXITCODE -eq 0) {
        # Compress backup using 7-Zip or built-in compression
        if (Get-Command Compress-Archive -ErrorAction SilentlyContinue) {
            Compress-Archive -Path $BackupFile -DestinationPath "${BackupFile}.zip" -Force
            Remove-Item $BackupFile
            $CompressedFile = "${BackupFile}.zip"
        }
        
        $BackupSize = (Get-Item $CompressedFile).Length / 1MB
        Log-Message "Backup completed successfully: $CompressedFile (Size: $([math]::Round($BackupSize, 2)) MB)"
        
        # Clean up old backups
        $CutoffDate = (Get-Date).AddDays(-$RetentionDays)
        Get-ChildItem -Path $BackupDir -Filter "backup_${DbName}_*" | 
            Where-Object { $_.LastWriteTime -lt $CutoffDate } | 
            Remove-Item -Force
        
        Log-Message "Cleaned up backups older than $RetentionDays days"
        
        exit 0
    } else {
        Log-Message "ERROR: Database backup failed with exit code $LASTEXITCODE"
        exit 1
    }
} catch {
    Log-Message "ERROR: Database backup failed: $($_.Exception.Message)"
    exit 1
}
