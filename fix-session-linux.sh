#!/bin/bash

# ==============================================================================
# Script: Fix Session Issues on Linux Deploy
# Description: Cấu hình và fix permissions cho session trên môi trường Linux
# ==============================================================================

echo "========================================"
echo "Fix Session Issues on Linux Deploy"
echo "========================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root or with sudo
if [ "$EUID" -eq 0 ]; then 
    SUDO=""
    echo -e "${YELLOW}Running as root${NC}"
else
    SUDO="sudo"
    echo -e "${YELLOW}Running with sudo${NC}"
fi

# Get web server user
WEB_USER="www-data"
if [ -f /etc/redhat-release ]; then
    WEB_USER="nginx"  # or apache
fi

echo -e "${GREEN}Web server user: ${WEB_USER}${NC}"
echo ""

# ==============================================================================
# Step 1: Fix Storage Permissions
# ==============================================================================
echo "Step 1: Fixing storage permissions..."

if [ -d "storage" ]; then
    echo "  - Setting permissions for storage/"
    $SUDO chmod -R 775 storage
    $SUDO chown -R $WEB_USER:$WEB_USER storage
    echo -e "${GREEN}  ✓ Storage permissions fixed${NC}"
else
    echo -e "${RED}  ✗ storage/ directory not found${NC}"
fi

if [ -d "storage/framework/sessions" ]; then
    echo "  - Setting permissions for storage/framework/sessions/"
    $SUDO chmod -R 775 storage/framework/sessions
    $SUDO chown -R $WEB_USER:$WEB_USER storage/framework/sessions
    echo -e "${GREEN}  ✓ Session directory permissions fixed${NC}"
else
    echo -e "${YELLOW}  ! storage/framework/sessions/ not found, creating...${NC}"
    mkdir -p storage/framework/sessions
    $SUDO chmod -R 775 storage/framework/sessions
    $SUDO chown -R $WEB_USER:$WEB_USER storage/framework/sessions
    echo -e "${GREEN}  ✓ Session directory created and permissions fixed${NC}"
fi

echo ""

# ==============================================================================
# Step 2: Fix Bootstrap Cache Permissions
# ==============================================================================
echo "Step 2: Fixing bootstrap/cache permissions..."

if [ -d "bootstrap/cache" ]; then
    echo "  - Setting permissions for bootstrap/cache/"
    $SUDO chmod -R 775 bootstrap/cache
    $SUDO chown -R $WEB_USER:$WEB_USER bootstrap/cache
    echo -e "${GREEN}  ✓ Bootstrap cache permissions fixed${NC}"
else
    echo -e "${RED}  ✗ bootstrap/cache/ directory not found${NC}"
fi

echo ""

# ==============================================================================
# Step 3: Check .env Configuration
# ==============================================================================
echo "Step 3: Checking .env configuration..."

if [ -f ".env" ]; then
    echo "  - Checking SESSION_DRIVER..."
    SESSION_DRIVER=$(grep "^SESSION_DRIVER=" .env | cut -d '=' -f2)
    
    if [ -z "$SESSION_DRIVER" ]; then
        echo -e "${YELLOW}  ! SESSION_DRIVER not set${NC}"
        echo "  Adding SESSION_DRIVER=database to .env"
        echo "SESSION_DRIVER=database" >> .env
    else
        echo -e "${GREEN}  ✓ SESSION_DRIVER=$SESSION_DRIVER${NC}"
        
        if [ "$SESSION_DRIVER" = "file" ]; then
            echo -e "${YELLOW}  ! Warning: file driver may have issues on Linux${NC}"
            echo -e "${YELLOW}  ! Consider changing to 'database' for better reliability${NC}"
        fi
    fi
    
    # Check SESSION_LIFETIME
    SESSION_LIFETIME=$(grep "^SESSION_LIFETIME=" .env | cut -d '=' -f2)
    if [ -z "$SESSION_LIFETIME" ]; then
        echo "  Adding SESSION_LIFETIME=480 to .env"
        echo "SESSION_LIFETIME=480" >> .env
    else
        echo -e "${GREEN}  ✓ SESSION_LIFETIME=$SESSION_LIFETIME${NC}"
    fi
    
    # Check SESSION_SECURE_COOKIE
    SESSION_SECURE=$(grep "^SESSION_SECURE_COOKIE=" .env | cut -d '=' -f2)
    if [ -z "$SESSION_SECURE" ]; then
        echo "  Adding SESSION_SECURE_COOKIE=false to .env"
        echo "SESSION_SECURE_COOKIE=false" >> .env
        echo -e "${YELLOW}  ! Set to true if using HTTPS${NC}"
    else
        echo -e "${GREEN}  ✓ SESSION_SECURE_COOKIE=$SESSION_SECURE${NC}"
    fi
    
    # Check SESSION_SAME_SITE
    SESSION_SAME_SITE=$(grep "^SESSION_SAME_SITE=" .env | cut -d '=' -f2)
    if [ -z "$SESSION_SAME_SITE" ]; then
        echo "  Adding SESSION_SAME_SITE=lax to .env"
        echo "SESSION_SAME_SITE=lax" >> .env
    else
        echo -e "${GREEN}  ✓ SESSION_SAME_SITE=$SESSION_SAME_SITE${NC}"
    fi
    
else
    echo -e "${RED}  ✗ .env file not found${NC}"
fi

echo ""

# ==============================================================================
# Step 4: Create Sessions Table (if using database driver)
# ==============================================================================
echo "Step 4: Checking sessions table..."

if [ -f ".env" ]; then
    SESSION_DRIVER=$(grep "^SESSION_DRIVER=" .env | cut -d '=' -f2)
    
    if [ "$SESSION_DRIVER" = "database" ]; then
        echo "  - Creating sessions table migration..."
        php artisan session:table 2>/dev/null
        
        echo "  - Running migrations..."
        php artisan migrate --force
        echo -e "${GREEN}  ✓ Sessions table migration completed${NC}"
    else
        echo -e "${YELLOW}  ! Not using database driver, skipping${NC}"
    fi
fi

echo ""

# ==============================================================================
# Step 5: Clear All Caches
# ==============================================================================
echo "Step 5: Clearing caches..."

echo "  - Clearing config cache..."
php artisan config:clear

echo "  - Clearing application cache..."
php artisan cache:clear

echo "  - Clearing view cache..."
php artisan view:clear

echo "  - Clearing route cache..."
php artisan route:clear

echo "  - Clearing compiled services..."
php artisan clear-compiled

echo "  - Running optimize:clear..."
php artisan optimize:clear

echo -e "${GREEN}  ✓ All caches cleared${NC}"

echo ""

# ==============================================================================
# Step 6: Test Session
# ==============================================================================
echo "Step 6: Testing session..."

# Create a PHP script to test session
cat > test_session_tmp.php << 'EOF'
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test session
try {
    session()->put('test_session_key', 'test_value_' . time());
    session()->save();
    
    $value = session()->get('test_session_key');
    
    if ($value) {
        echo "✓ Session test PASSED: " . $value . PHP_EOL;
        exit(0);
    } else {
        echo "✗ Session test FAILED: Could not retrieve session value" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Session test ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
EOF

php test_session_tmp.php
TEST_RESULT=$?

# Clean up
rm -f test_session_tmp.php

if [ $TEST_RESULT -eq 0 ]; then
    echo -e "${GREEN}  ✓ Session test passed${NC}"
else
    echo -e "${RED}  ✗ Session test failed${NC}"
    echo -e "${YELLOW}  ! Check logs: tail -f storage/logs/laravel.log${NC}"
fi

echo ""

# ==============================================================================
# Summary
# ==============================================================================
echo "========================================"
echo "Summary"
echo "========================================"
echo ""
echo "✓ Storage permissions fixed"
echo "✓ Bootstrap cache permissions fixed"
echo "✓ .env configuration checked/updated"
echo "✓ Caches cleared"
echo ""

if [ -f ".env" ]; then
    SESSION_DRIVER=$(grep "^SESSION_DRIVER=" .env | cut -d '=' -f2)
    echo "Current SESSION_DRIVER: $SESSION_DRIVER"
fi

echo ""
echo "========================================"
echo "Next Steps:"
echo "========================================"
echo "1. Verify .env configuration:"
echo "   - SESSION_DRIVER (recommend: database)"
echo "   - SESSION_SECURE_COOKIE (true for HTTPS)"
echo "   - SESSION_SAME_SITE (recommend: lax)"
echo ""
echo "2. Test the forgot password flow:"
echo "   - Go to /forgot-password"
echo "   - Enter email and verify OTP"
echo "   - Check if redirect works correctly"
echo ""
echo "3. Monitor logs:"
echo "   tail -f storage/logs/laravel.log | grep password_reset"
echo ""
echo "========================================"

exit 0

