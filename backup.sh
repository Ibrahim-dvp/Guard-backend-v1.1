# Production Database Backup Script
# This script should be placed in /usr/local/bin/backup-guard-db.sh

#!/bin/bash

# Configuration
BACKUP_DIR="/backup/guard"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Database Configuration
DB_NAME="guard_backend"
DB_USER="guard_user"
DB_PASS="your_secure_database_password"
DB_HOST="127.0.0.1"
DB_PORT="3306"

# Application Configuration
APP_DIR="/var/www/api.zmachine.pro"
APP_NAME="guard-backend"

# Notification Configuration
SLACK_WEBHOOK=""  # Add your Slack webhook URL here
ADMIN_EMAIL="admin@zmachine.pro"

# Create backup directory
mkdir -p $BACKUP_DIR
cd $BACKUP_DIR

# Logging
LOG_FILE="$BACKUP_DIR/backup.log"
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a $LOG_FILE
}

log_message "Starting backup process..."

# Function to send notifications
send_notification() {
    local status=$1
    local message=$2
    
    # Log the message
    log_message "$message"
    
    # Send email notification
    if command -v mail &> /dev/null; then
        echo "$message" | mail -s "$APP_NAME Backup $status" $ADMIN_EMAIL
    fi
    
    # Send Slack notification
    if [ ! -z "$SLACK_WEBHOOK" ]; then
        curl -X POST $SLACK_WEBHOOK \
             -H 'Content-type: application/json' \
             --data "{\"text\":\"📦 $APP_NAME Backup $status: $message\"}"
    fi
}

# Check if MySQL is accessible
if ! mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS -e "SELECT 1" &> /dev/null; then
    send_notification "FAILED" "Cannot connect to MySQL database"
    exit 1
fi

# Create database backup
log_message "Creating database backup..."
BACKUP_FILE="$APP_NAME-db-$DATE.sql"

if mysqldump -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-table \
    --complete-insert \
    --extended-insert \
    --set-charset \
    --disable-keys \
    --lock-tables=false \
    $DB_NAME > $BACKUP_FILE; then
    
    log_message "Database backup created successfully: $BACKUP_FILE"
    
    # Compress the backup
    gzip $BACKUP_FILE
    BACKUP_FILE="$BACKUP_FILE.gz"
    
    # Get backup size
    BACKUP_SIZE=$(du -h $BACKUP_FILE | cut -f1)
    log_message "Backup compressed to: $BACKUP_FILE (Size: $BACKUP_SIZE)"
    
else
    send_notification "FAILED" "Database backup failed"
    exit 1
fi

# Create application files backup (optional - only critical files)
log_message "Creating application files backup..."
APP_BACKUP_FILE="$APP_NAME-files-$DATE.tar.gz"

cd $APP_DIR
tar -czf $BACKUP_DIR/$APP_BACKUP_FILE \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    .env config/ storage/app/ public/uploads/ || true

if [ -f "$BACKUP_DIR/$APP_BACKUP_FILE" ]; then
    APP_BACKUP_SIZE=$(du -h $BACKUP_DIR/$APP_BACKUP_FILE | cut -f1)
    log_message "Application files backup created: $APP_BACKUP_FILE (Size: $APP_BACKUP_SIZE)"
else
    log_message "Warning: Application files backup creation failed"
fi

# Clean up old backups
log_message "Cleaning up old backups (keeping last $RETENTION_DAYS days)..."
find $BACKUP_DIR -name "$APP_NAME-db-*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "$APP_NAME-files-*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Count remaining backups
DB_BACKUP_COUNT=$(find $BACKUP_DIR -name "$APP_NAME-db-*.sql.gz" | wc -l)
FILES_BACKUP_COUNT=$(find $BACKUP_DIR -name "$APP_NAME-files-*.tar.gz" | wc -l)

log_message "Cleanup completed. Remaining backups: DB=$DB_BACKUP_COUNT, Files=$FILES_BACKUP_COUNT"

# Verify backup integrity
log_message "Verifying backup integrity..."
if gzip -t $BACKUP_DIR/$BACKUP_FILE; then
    log_message "Backup integrity verification passed"
    send_notification "SUCCESS" "Backup completed successfully. Size: $BACKUP_SIZE"
else
    send_notification "FAILED" "Backup integrity verification failed"
    exit 1
fi

# Upload to remote storage (optional - AWS S3 example)
# Uncomment and configure if you want to upload to S3
# if command -v aws &> /dev/null; then
#     log_message "Uploading backup to S3..."
#     aws s3 cp $BACKUP_DIR/$BACKUP_FILE s3://your-backup-bucket/guard-backend/
#     if [ $? -eq 0 ]; then
#         log_message "Backup uploaded to S3 successfully"
#     else
#         log_message "Warning: S3 upload failed"
#     fi
# fi

log_message "Backup process completed successfully"

# Display summary
echo ""
echo "📊 Backup Summary:"
echo "   Database: $BACKUP_FILE ($BACKUP_SIZE)"
echo "   Files: $APP_BACKUP_FILE ($APP_BACKUP_SIZE)"
echo "   Location: $BACKUP_DIR"
echo "   Total DB backups: $DB_BACKUP_COUNT"
echo "   Total file backups: $FILES_BACKUP_COUNT"
echo ""
