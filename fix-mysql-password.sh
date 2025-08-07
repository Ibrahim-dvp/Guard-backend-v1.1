#!/bin/bash

# MySQL Password Policy Fix Script
# Run this if you're having issues with MySQL password policy

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 MySQL Password Policy Fix Script${NC}"
echo -e "${BLUE}====================================${NC}"
echo ""

DB_NAME="guard_backend"
DB_USER="guard_user"

print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

echo -e "${YELLOW}This script will help you fix MySQL password policy issues.${NC}"
echo ""

# Option 1: Create a strong password that meets policy requirements
echo -e "${BLUE}Option 1: Create a strong password${NC}"
echo -e "${YELLOW}Enter a strong password for the database user:${NC}"
echo -e "${YELLOW}Requirements: 8+ chars, 1 uppercase, 1 lowercase, 1 number, 1 special char${NC}"
echo -e "${YELLOW}Example: GuardPass123!${NC}"
read -s NEW_PASSWORD
echo ""

# Validate password strength
if [[ ${#NEW_PASSWORD} -lt 8 ]]; then
    print_error "Password too short (minimum 8 characters)"
    exit 1
fi

if ! [[ "$NEW_PASSWORD" =~ [A-Z] ]]; then
    print_error "Password must contain at least one uppercase letter"
    exit 1
fi

if ! [[ "$NEW_PASSWORD" =~ [a-z] ]]; then
    print_error "Password must contain at least one lowercase letter"
    exit 1
fi

if ! [[ "$NEW_PASSWORD" =~ [0-9] ]]; then
    print_error "Password must contain at least one number"
    exit 1
fi

if ! [[ "$NEW_PASSWORD" =~ [^A-Za-z0-9] ]]; then
    print_error "Password must contain at least one special character"
    exit 1
fi

print_status "Password meets policy requirements"

# Try to create database and user
echo ""
echo -e "${BLUE}Creating database and user...${NC}"

# Check if MySQL requires root password
if sudo mysql -e "SELECT 1;" &>/dev/null; then
    # MySQL accessible without password
    echo "Using sudo mysql access..."
    
    sudo mysql -e "DROP USER IF EXISTS '$DB_USER'@'localhost';"
    sudo mysql -e "DROP DATABASE IF EXISTS $DB_NAME;"
    sudo mysql -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    sudo mysql -e "CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$NEW_PASSWORD';"
    sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
    sudo mysql -e "FLUSH PRIVILEGES;"
    
else
    # MySQL requires root password
    echo -e "${YELLOW}Please enter MySQL root password:${NC}"
    read -s MYSQL_ROOT_PASSWORD
    echo ""
    
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP USER IF EXISTS '$DB_USER'@'localhost';"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS $DB_NAME;"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$NEW_PASSWORD';"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;"
fi

# Test connection
echo ""
echo -e "${BLUE}Testing database connection...${NC}"
if mysql -u "$DB_USER" -p"$NEW_PASSWORD" -e "USE $DB_NAME; SELECT 1;" &>/dev/null; then
    print_status "Database connection successful!"
else
    print_error "Database connection failed!"
    exit 1
fi

# Update .env file if it exists
ENV_FILE="/var/www/api.zmachine.pro/.env"
if [[ -f "$ENV_FILE" ]]; then
    echo ""
    echo -e "${BLUE}Updating .env file...${NC}"
    
    # Backup original .env
    sudo cp "$ENV_FILE" "$ENV_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Update database password in .env
    sudo sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$NEW_PASSWORD|g" "$ENV_FILE"
    print_status ".env file updated"
    
    # Test Laravel database connection
    cd /var/www/api.zmachine.pro
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Laravel DB OK';" 2>/dev/null | grep -q "Laravel DB OK"; then
        print_status "Laravel database connection successful!"
    else
        print_warning "Laravel database connection test failed - may need to clear cache"
        php artisan config:clear
        php artisan cache:clear
    fi
else
    print_warning ".env file not found at $ENV_FILE"
fi

echo ""
echo -e "${GREEN}🎉 Database setup completed successfully!${NC}"
echo ""
echo -e "${BLUE}📊 Database Details:${NC}"
echo -e "   Database: $DB_NAME"
echo -e "   User: $DB_USER"
echo -e "   Password: [HIDDEN - saved in .env file]"
echo ""
echo -e "${BLUE}🔧 Next Steps:${NC}"
echo -e "1. Continue with the deployment process"
echo -e "2. If running quick-setup.sh, restart from where it failed"
echo -e "3. Test API: curl https://api.zmachine.pro/api/health"
echo ""
echo -e "${YELLOW}💡 Password Requirements for Future Reference:${NC}"
echo -e "   - Minimum 8 characters"
echo -e "   - At least 1 uppercase letter (A-Z)"
echo -e "   - At least 1 lowercase letter (a-z)"
echo -e "   - At least 1 number (0-9)"
echo -e "   - At least 1 special character (!@#$%^&*)"
