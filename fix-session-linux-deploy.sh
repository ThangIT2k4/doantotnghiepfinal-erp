#!/bin/bash
# Script để fix session configuration trên Linux hosting

echo "=== Fix Session Configuration cho Linux Hosting ==="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}✗ File .env không tồn tại!${NC}"
    exit 1
fi

echo "1. Kiểm tra và cập nhật SESSION configuration trong .env..."
echo ""

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo -e "${GREEN}✓ Đã backup .env${NC}"

# Function to update or add env variable
update_env() {
    local key=$1
    local value=$2
    local comment=$3
    
    if grep -q "^${key}=" .env; then
        # Update existing
        sed -i "s|^${key}=.*|${key}=${value}|" .env
        echo -e "${GREEN}✓ Updated ${key}=${value}${NC}"
    else
        # Add new
        if [ -n "$comment" ]; then
            echo "" >> .env
            echo "# $comment" >> .env
        fi
        echo "${key}=${value}" >> .env
        echo -e "${GREEN}✓ Added ${key}=${value}${NC}"
    fi
}

# Update session settings for HTTPS
update_env "SESSION_DRIVER" "database" "Session driver - use database for reliability on Linux"
update_env "SESSION_LIFETIME" "480" "Session lifetime in minutes (8 hours)"
update_env "SESSION_SECURE_COOKIE" "true" "Set to true for HTTPS (auto-detected if null)"
update_env "SESSION_SAME_SITE" "lax" "SameSite cookie attribute (lax for same-site, none for cross-site)"
update_env "SESSION_HTTP_ONLY" "true" "HTTP only cookies (security)"
update_env "SESSION_DOMAIN" "" "Leave empty for auto-detect, or set to .yourdomain.com for subdomains"

echo ""
echo "2. Kiểm tra storage permissions..."
if [ -d "storage/framework/sessions" ]; then
    chmod -R 775 storage/framework/sessions
    echo -e "${GREEN}✓ Set permissions for storage/framework/sessions${NC}"
fi

echo ""
echo "3. Clear cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo -e "${GREEN}=== Hoàn tất! ===${NC}"
echo ""
echo "Lưu ý:"
echo "- SESSION_SECURE_COOKIE=true cho HTTPS"
echo "- Nếu vẫn gặp lỗi 'Tracking Prevention', thử set SESSION_SAME_SITE=none (cần secure=true)"
echo "- Kiểm tra SESSION_DOMAIN nếu dùng subdomain"
echo ""
echo "Để test, chạy:"
echo "  tail -f storage/logs/laravel.log | grep -i session"

