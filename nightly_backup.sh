#!/bin/bash
# Bondor Bati POS - Nightly Automated Backup

# Configuration
DB_USER="root"
DB_PASS=""
DB_NAME="bondor_bati"
BACKUP_DIR="/opt/lampp/htdocs/bondor-bati/backups/automated"
DATE=$(date +"%Y-%m-%d_%H-%M")
FILE_NAME="db_backup_$DATE.sql"

# 1. Create the backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# 2. Export the database using XAMPP's mysqldump
/opt/lampp/bin/mysqldump -u $DB_USER $DB_NAME > $BACKUP_DIR/$FILE_NAME

# 3. Security: Keep only the last 7 days of backups to save disk space
find $BACKUP_DIR -type f -name "*.sql" -mtime +7 -exec rm {} \;

# 4. Log the success
echo "Backup $FILE_NAME created successfully at $(date)"