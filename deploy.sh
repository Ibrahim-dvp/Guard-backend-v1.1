#!/bin/bash

# Guard Backend Deployment Script
# Usage: ./deploy.sh [environment] [--auto]
# Example: ./deploy.sh production
# Example: ./deploy.sh production --auto (for webhook deployments)

set -e

ENVIRONMENT=${1:-production}
AUTO_MODE=${2:-""}
PROJECT_DIR="/var/www/api.zmachine.pro"
BACKUP_DIR="/backup/guard"
DATE=$(date +%Y%m%d_%H%M%S)
APP_URL="https://api.zmachine.pro"

echo "🚀 Starting Guard Backend deployment for $ENVIRONMENT environment..."

# Auto-mode detection (when called by webhook)
if [[ "$AUTO_MODE" == "--auto" ]]; then
    echo "🤖 Running in automated deployment mode"
fi Guard Backend Deployment Script
# Usage: ./deploy.sh [environment]
# Example: ./deploy.sh production

set -e

ENVIRONMENT=${1:-production}
PROJECT_DIR="/var/www/api.zmachine.pro"
BACKUP_DIR="/backup/guard"
DATE=$(date +%Y%m%d_%H%M%S)

echo "🚀 Starting Guard Backend deployment for $ENVIRONMENT environment..."

# Change to project directory
cd $PROJECT_DIR

# Create backup directory if it doesn't exist
sudo mkdir -p $BACKUP_DIR

# 1. Backup current database
echo "📦 Creating database backup..."
if command -v mysqldump &> /dev/null; then
    # Create backup using mysqldump directly
    DB_NAME=$(php -r "echo env('DB_DATABASE');")
    DB_USER=$(php -r "echo env('DB_USERNAME');")
    DB_PASS=$(php -r "echo env('DB_PASSWORD');")
    
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/guard_backup_$DATE.sql"
    gzip "$BACKUP_DIR/guard_backup_$DATE.sql"
    echo "✅ Database backup created: guard_backup_$DATE.sql.gz"
else
    echo "⚠️ mysqldump not available, skipping database backup"
fi

# 2. Put application in maintenance mode
echo "🔧 Putting application in maintenance mode..."
php artisan down --render="errors::503" --secret="guard-deployment-$DATE"

# 3. Pull latest changes from repository
echo "⬇️ Pulling latest changes..."
git fetch --all
git reset --hard origin/main
git pull origin main

# 4. Install/Update Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# 5. Clear all Laravel caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# 6. Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force --no-interaction

# 7. Seed database if needed (comment out for production updates)
# echo "🌱 Seeding database..."
# php artisan db:seed --force --no-interaction

# 8. Cache configurations
echo "⚡ Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 9. Generate API documentation (if you have it)
# php artisan l5-swagger:generate

# 10. Clear and warm up application cache
echo "🔥 Warming up application cache..."
php artisan cache:clear
php artisan queue:restart

# 11. Set proper file permissions
echo "🔐 Setting file permissions..."
sudo chown -R www-data:www-data $PROJECT_DIR
sudo chmod -R 755 $PROJECT_DIR
sudo chmod -R 775 $PROJECT_DIR/storage
sudo chmod -R 775 $PROJECT_DIR/bootstrap/cache

# 12. Restart services
echo "🔄 Restarting services..."
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx

# 13. Clear OpCache if enabled
if command -v php-fpm8.2 &> /dev/null; then
    echo "🧹 Clearing OpCache..."
    sudo systemctl reload php8.2-fpm
fi

# 14. Bring application back online
echo "✅ Bringing application back online..."
php artisan up

# 15. Run health checks
echo "🏥 Running health checks..."
php artisan about

# 16. Test API endpoints
echo "🧪 Testing critical API endpoints..."
curl -f $APP_URL/api/health || echo "⚠️ Health check endpoint failed"

# 17. Clean up old releases if this was a git-based deployment
echo "🧹 Cleaning up..."
find $BACKUP_DIR -name "guard_backup_*.sql" -mtime +7 -delete

# 18. Send deployment notification (optional)
# curl -X POST "https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK" \
#      -H 'Content-type: application/json' \
#      --data '{"text":"🚀 Guard Backend deployed successfully to production!"}'

echo ""
echo "🎉 Deployment completed successfully!"
echo "📊 Deployment Summary:"
echo "   Environment: $ENVIRONMENT"
echo "   Time: $(date)"
echo "   Backup: guard_backup_$DATE.sql"
echo "   URL: $APP_URL"
echo ""
echo "🔗 Useful commands:"
echo "   View logs: tail -f storage/logs/laravel.log"
echo "   Check status: php artisan about"
echo "   Queue status: php artisan queue:monitor"
echo ""

# Exit maintenance mode secret for emergency access
echo "🔓 Emergency maintenance mode exit secret: guard-deployment-$DATE"
