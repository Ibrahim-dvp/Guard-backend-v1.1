#!/bin/bash

# Guard Backend Server Monitoring Script
# Run this script via cron every 5 minutes to monitor the application

PROJECT_DIR="/var/www/api.zmachine.pro"
LOG_FILE="/var/log/guard-monitor.log"
ALERT_EMAIL="admin@zmachine.pro"
API_URL="https://api.zmachine.pro"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}

# Check if service is running
check_service() {
    local service=$1
    if systemctl is-active --quiet $service; then
        echo -e "${GREEN}✓${NC} $service is running"
        return 0
    else
        echo -e "${RED}✗${NC} $service is not running"
        log_message "ERROR: $service is not running"
        return 1
    fi
}

# Check disk usage
check_disk_usage() {
    local threshold=85
    local usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ $usage -lt $threshold ]; then
        echo -e "${GREEN}✓${NC} Disk usage: ${usage}%"
    else
        echo -e "${RED}✗${NC} Disk usage critical: ${usage}%"
        log_message "WARNING: Disk usage is ${usage}%"
        return 1
    fi
}

# Check memory usage
check_memory_usage() {
    local threshold=85
    local usage=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    
    if [ $usage -lt $threshold ]; then
        echo -e "${GREEN}✓${NC} Memory usage: ${usage}%"
    else
        echo -e "${YELLOW}⚠${NC} Memory usage high: ${usage}%"
        log_message "WARNING: Memory usage is ${usage}%"
    fi
}

# Check API health
check_api_health() {
    local response=$(curl -s -o /dev/null -w "%{http_code}" $API_URL/api/health)
    
    if [ "$response" = "200" ]; then
        echo -e "${GREEN}✓${NC} API health check passed"
    else
        echo -e "${RED}✗${NC} API health check failed (HTTP $response)"
        log_message "ERROR: API health check failed with HTTP $response"
        return 1
    fi
}

# Check database connection
check_database() {
    cd $PROJECT_DIR
    local db_check=$(php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | tail -1)
    
    if [ "$db_check" = "OK" ]; then
        echo -e "${GREEN}✓${NC} Database connection successful"
    else
        echo -e "${RED}✗${NC} Database connection failed"
        log_message "ERROR: Database connection failed"
        return 1
    fi
}

# Check Laravel queue status
check_queue_status() {
    cd $PROJECT_DIR
    local failed_jobs=$(php artisan queue:failed --format=json | jq length 2>/dev/null || echo "0")
    
    if [ "$failed_jobs" = "0" ]; then
        echo -e "${GREEN}✓${NC} No failed queue jobs"
    else
        echo -e "${YELLOW}⚠${NC} $failed_jobs failed queue jobs"
        log_message "WARNING: $failed_jobs failed queue jobs found"
    fi
}

# Check SSL certificate expiry
check_ssl_certificate() {
    local domain="api.zmachine.pro"
    local expiry_date=$(echo | openssl s_client -servername $domain -connect $domain:443 2>/dev/null | openssl x509 -noout -dates | grep notAfter | cut -d= -f2)
    local expiry_timestamp=$(date -d "$expiry_date" +%s)
    local current_timestamp=$(date +%s)
    local days_until_expiry=$(( (expiry_timestamp - current_timestamp) / 86400 ))
    
    if [ $days_until_expiry -gt 30 ]; then
        echo -e "${GREEN}✓${NC} SSL certificate valid for $days_until_expiry days"
    elif [ $days_until_expiry -gt 7 ]; then
        echo -e "${YELLOW}⚠${NC} SSL certificate expires in $days_until_expiry days"
        log_message "WARNING: SSL certificate expires in $days_until_expiry days"
    else
        echo -e "${RED}✗${NC} SSL certificate expires in $days_until_expiry days"
        log_message "CRITICAL: SSL certificate expires in $days_until_expiry days"
        return 1
    fi
}

# Check Laravel logs for errors
check_laravel_logs() {
    local log_file="$PROJECT_DIR/storage/logs/laravel.log"
    local error_count=$(tail -n 100 $log_file 2>/dev/null | grep -c "ERROR\|CRITICAL\|EMERGENCY" || echo "0")
    
    if [ $error_count -eq 0 ]; then
        echo -e "${GREEN}✓${NC} No recent Laravel errors"
    else
        echo -e "${YELLOW}⚠${NC} $error_count recent Laravel errors found"
        log_message "WARNING: $error_count Laravel errors found in last 100 lines"
    fi
}

# Check file permissions
check_file_permissions() {
    local storage_writable=$(test -w "$PROJECT_DIR/storage" && echo "yes" || echo "no")
    local cache_writable=$(test -w "$PROJECT_DIR/bootstrap/cache" && echo "yes" || echo "no")
    
    if [ "$storage_writable" = "yes" ] && [ "$cache_writable" = "yes" ]; then
        echo -e "${GREEN}✓${NC} File permissions are correct"
    else
        echo -e "${RED}✗${NC} File permission issues detected"
        log_message "ERROR: File permission issues - storage: $storage_writable, cache: $cache_writable"
        return 1
    fi
}

# Send alert email
send_alert() {
    local subject="$1"
    local message="$2"
    
    echo "$message" | mail -s "$subject" $ALERT_EMAIL 2>/dev/null || \
    curl -X POST "https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK" \
         -H 'Content-type: application/json' \
         --data "{\"text\":\"🚨 $subject: $message\"}" 2>/dev/null || \
    echo "Alert: $subject - $message" >> $LOG_FILE
}

# Main monitoring function
main() {
    echo "🔍 Guard Backend Health Check - $(date)"
    echo "========================================"
    
    local failed_checks=0
    
    # System checks
    echo "System Services:"
    check_service "nginx" || ((failed_checks++))
    check_service "php8.2-fpm" || ((failed_checks++))
    check_service "mysql" || ((failed_checks++))
    
    echo ""
    echo "System Resources:"
    check_disk_usage || ((failed_checks++))
    check_memory_usage
    
    echo ""
    echo "Application Health:"
    check_api_health || ((failed_checks++))
    check_database || ((failed_checks++))
    check_queue_status
    check_laravel_logs
    check_file_permissions || ((failed_checks++))
    
    echo ""
    echo "Security:"
    check_ssl_certificate || ((failed_checks++))
    
    echo ""
    if [ $failed_checks -eq 0 ]; then
        echo -e "${GREEN}🎉 All checks passed!${NC}"
        log_message "INFO: All health checks passed"
    else
        echo -e "${RED}❌ $failed_checks checks failed${NC}"
        log_message "ERROR: $failed_checks health checks failed"
        send_alert "Guard Backend Alert" "$failed_checks health checks failed on $(hostname)"
    fi
    
    echo ""
}

# Create log file if it doesn't exist
touch $LOG_FILE

# Run the health check
main

# If running as cron, don't show output
if [ -t 1 ]; then
    # Running in terminal, show output
    :
else
    # Running as cron, log only
    main >> $LOG_FILE 2>&1
fi
