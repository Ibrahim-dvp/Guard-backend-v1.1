#!/bin/bash

# Guard Backend Quick Setup Script
# This script automates the initial setup process for new VPS deployments

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="api.zmachine.pro"
PROJECT_DIR="/var/www/$DOMAIN"
DB_NAME="guard_backend"
DB_USER="guard_user"
REPO_URL="https://github.com/ElhassaneMhd/Guard-backend-v1.1.git"

echo -e "${BLUE}🚀 Guard Backend Quick Setup Script${NC}"
echo -e "${BLUE}=====================================${NC}"
echo ""

# Function to print status
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if running as root or with sudo
if [[ $EUID -eq 0 ]]; then
    SUDO=""
    print_warning "Running as root user"
else
    SUDO="sudo"
    print_status "Running with sudo privileges"
fi

# Get database password
echo -e "${YELLOW}Please enter a secure password for the database user (minimum 8 characters):${NC}"
echo -e "${YELLOW}Password requirements: At least 8 characters, 1 uppercase, 1 lowercase, 1 number${NC}"
while true; do
    read -s DB_PASSWORD
    echo ""
    
    # Check if password is empty
    if [[ -z "$DB_PASSWORD" ]]; then
        echo -e "${RED}Error: Password cannot be empty!${NC}"
        echo -e "${YELLOW}Please enter a secure password:${NC}"
        continue
    fi
    
    # Check minimum length
    if [[ ${#DB_PASSWORD} -lt 8 ]]; then
        echo -e "${RED}Error: Password must be at least 8 characters long!${NC}"
        echo -e "${YELLOW}Please enter a secure password:${NC}"
        continue
    fi
    
    # Password is valid
    break
done

# Get admin email for SSL certificate
echo -e "${YELLOW}Please enter your email for SSL certificate registration:${NC}"
read ADMIN_EMAIL
echo ""

echo -e "${BLUE}Starting setup process...${NC}"
echo ""

# 1. Update system
echo "📦 Updating system packages..."
$SUDO apt update && $SUDO apt upgrade -y
print_status "System updated"

# 2. Install PHP and extensions
echo "🐘 Installing PHP 8.2 and extensions..."
$SUDO apt install software-properties-common -y
$SUDO add-apt-repository ppa:ondrej/php -y
$SUDO apt update
$SUDO apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl php8.2-tokenizer -y
print_status "PHP 8.2 installed"

# 3. Install MySQL
echo "🗄️ Installing MySQL..."
$SUDO apt install mysql-server -y
print_status "MySQL installed"

# 4. Install Nginx
echo "🌐 Installing Nginx..."
$SUDO apt install nginx -y
print_status "Nginx installed"

# 5. Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
$SUDO mv composer.phar /usr/local/bin/composer
$SUDO chmod +x /usr/local/bin/composer
print_status "Composer installed"

# 6. Install Git
echo "📁 Installing Git..."
$SUDO apt install git -y
print_status "Git installed"

# 7. Install Certbot for SSL
echo "🔒 Installing Certbot..."
$SUDO apt install certbot python3-certbot-nginx -y
print_status "Certbot installed"

# 8. Setup MySQL database
echo "🗄️ Setting up MySQL database..."

# First, secure MySQL installation and set root password if needed
echo "🔐 Securing MySQL installation..."
$SUDO mysql_secure_installation --use-default || {
    echo "⚠️ MySQL secure installation failed, continuing with manual setup..."
}

# Check if we can connect to MySQL and create database
echo "🗄️ Creating database and user..."
if $SUDO mysql -e "SELECT 1;" &>/dev/null; then
    # MySQL accessible without password (default after fresh install)
    $SUDO mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    $SUDO mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
    $SUDO mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
    $SUDO mysql -e "FLUSH PRIVILEGES;"
else
    # If MySQL requires root password, prompt for it
    echo -e "${YELLOW}MySQL requires root password. Please enter MySQL root password:${NC}"
    read -s MYSQL_ROOT_PASSWORD
    echo ""
    
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;"
fi

# Test the database connection
if mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT 1;" &>/dev/null; then
    print_status "Database and user created successfully"
else
    print_error "Failed to create database or user. Please check the password requirements."
    echo -e "${YELLOW}MySQL password policy requirements:${NC}"
    echo -e "  - At least 8 characters"
    echo -e "  - At least 1 uppercase letter"
    echo -e "  - At least 1 lowercase letter" 
    echo -e "  - At least 1 number"
    echo -e "  - At least 1 special character"
    exit 1
fi

# 9. Create application directory
echo "📁 Creating application directory..."
$SUDO mkdir -p $PROJECT_DIR
$SUDO chown -R $USER:$USER $PROJECT_DIR
print_status "Application directory created"

# 10. Clone repository
echo "📥 Cloning repository..."
cd $PROJECT_DIR
git clone $REPO_URL .
print_status "Repository cloned"

# 11. Make scripts executable
chmod +x deploy.sh backup.sh monitor.sh
print_status "Scripts made executable"

# 12. Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev
print_status "Composer dependencies installed"

# 13. Setup environment file
echo "⚙️ Setting up environment file..."
cp .env.production .env

# Update .env file with actual values
sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|g" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_NAME|g" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USER|g" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|g" .env
sed -i "s|api.zmachine.pro|$DOMAIN|g" .env
sed -i "s|zmachine.pro|zmachine.pro|g" .env

# Generate application key
php artisan key:generate

print_status "Environment configured"

# 14. Setup Nginx configuration
echo "🌐 Setting up Nginx configuration..."
$SUDO cp nginx.conf /etc/nginx/sites-available/$DOMAIN

# Update Nginx config with actual domain
$SUDO sed -i "s|api.zmachine.pro|$DOMAIN|g" /etc/nginx/sites-available/$DOMAIN
$SUDO sed -i "s|zmachine.pro|zmachine.pro|g" /etc/nginx/sites-available/$DOMAIN

# Enable site
$SUDO ln -s /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
$SUDO rm -f /etc/nginx/sites-enabled/default

# Test Nginx config
$SUDO nginx -t
$SUDO systemctl restart nginx

print_status "Nginx configured"

# 15. Setup SSL certificate
echo "🔒 Setting up SSL certificate..."
$SUDO certbot --nginx -d $DOMAIN --email $ADMIN_EMAIL --agree-tos --no-eff-email --quiet
$SUDO systemctl enable certbot.timer
$SUDO systemctl start certbot.timer
print_status "SSL certificate installed"

# 16. Set proper permissions
echo "🔐 Setting file permissions..."
$SUDO chown -R www-data:www-data $PROJECT_DIR
$SUDO chmod -R 755 $PROJECT_DIR
$SUDO chmod -R 775 $PROJECT_DIR/storage
$SUDO chmod -R 775 $PROJECT_DIR/bootstrap/cache
print_status "File permissions set"

# 17. Setup firewall
echo "🛡️ Setting up firewall..."
$SUDO ufw allow 22
$SUDO ufw allow 80
$SUDO ufw allow 443
echo "y" | $SUDO ufw enable
print_status "Firewall configured"

# 18. Run initial deployment
echo "🚀 Running initial deployment..."
cd $PROJECT_DIR
./deploy.sh production
print_status "Initial deployment completed"

# 19. Setup monitoring and backup cron jobs
echo "📊 Setting up cron jobs..."
(crontab -l 2>/dev/null; echo "*/5 * * * * $PROJECT_DIR/monitor.sh") | crontab -
(crontab -l 2>/dev/null; echo "0 2 * * * $PROJECT_DIR/backup.sh") | crontab -
print_status "Cron jobs configured"

# 20. Create webhook log file
echo "📝 Setting up webhook logging..."
$SUDO touch /var/log/guard-auto-deploy.log
$SUDO chown www-data:www-data /var/log/guard-auto-deploy.log

# Setup log rotation
echo "/var/log/guard-auto-deploy.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}" | $SUDO tee /etc/logrotate.d/guard-auto-deploy

print_status "Webhook logging configured"

echo ""
echo -e "${GREEN}🎉 Setup completed successfully!${NC}"
echo ""
echo -e "${BLUE}📊 Setup Summary:${NC}"
echo -e "   Domain: https://$DOMAIN"
echo -e "   Project Directory: $PROJECT_DIR"
echo -e "   Database: $DB_NAME"
echo -e "   Database User: $DB_USER"
echo -e "   SSL Certificate: ✅ Installed"
echo -e "   Firewall: ✅ Configured"
echo -e "   Monitoring: ✅ Every 5 minutes"
echo -e "   Backups: ✅ Daily at 2 AM"
echo ""
echo -e "${BLUE}🔗 Next Steps:${NC}"
echo -e "1. Test your API: curl https://$DOMAIN/api/health"
echo -e "2. Setup GitHub webhook: https://$DOMAIN/webhook"
echo -e "3. Update Postman collection base URL to: https://$DOMAIN"
echo -e "4. Configure webhook secret in webhook.php"
echo ""
echo -e "${BLUE}🔧 Useful Commands:${NC}"
echo -e "   Deploy manually: cd $PROJECT_DIR && ./deploy.sh production"
echo -e "   View logs: tail -f $PROJECT_DIR/storage/logs/laravel.log"
echo -e "   Monitor system: $PROJECT_DIR/monitor.sh"
echo -e "   View webhook logs: sudo tail -f /var/log/guard-auto-deploy.log"
echo ""
echo -e "${GREEN}Your Guard Backend API is now live at https://$DOMAIN! 🚀${NC}"
