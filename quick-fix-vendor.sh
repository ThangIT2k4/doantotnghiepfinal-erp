#!/bin/bash
# EMERGENCY FIX: Xoá vendor + reinstall khi bị lỗi autoload
# Run này TRONG container khi gặp: "Failed opening required: thecodingmachine/safe/lib/special_cases.php"

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}=== EMERGENCY VENDOR FIX ===${NC}\n"

# 1. Backup composer.lock (just in case)
if [ -f "composer.lock" ]; then
    echo -e "${YELLOW}Backing up composer.lock...${NC}"
    cp composer.lock composer.lock.backup.$(date +%s)
    echo -e "${GREEN}✓ Backup created${NC}"
fi

# 2. Remove vendor
echo -e "${YELLOW}Removing vendor/...${NC}"
if [ -d "vendor" ]; then
    rm -rf vendor
    echo -e "${GREEN}✓ vendor/ removed${NC}"
else
    echo -e "${YELLOW}vendor/ not found (already clean)${NC}"
fi

# 3. Fresh install
echo -e "${YELLOW}Running composer install...${NC}"
COMPOSER_MEMORY_LIMIT=-1 composer install \
    --no-dev \
    --optimize-autoloader \
    --prefer-dist \
    --no-interaction \
    --verbose

echo ""
echo -e "${YELLOW}Dumping autoload...${NC}"
composer dump-autoload --optimize --classmap-authoritative --no-dev

# 4. Verify
echo ""
echo -e "${YELLOW}Verifying...${NC}"
if [ ! -f "vendor/composer/autoload_real.php" ]; then
    echo -e "${RED}✗ FAILED: autoload_real.php still missing${NC}"
    exit 1
fi

if [ ! -f "vendor/thecodingmachine/safe/lib/special_cases.php" ]; then
    echo -e "${RED}✗ FAILED: thecodingmachine/safe/lib/special_cases.php still missing${NC}"
    ls -la vendor/thecodingmachine/safe/lib/ || true
    exit 1
fi

echo -e "${GREEN}✓ Verification passed${NC}"

# 5. Test
echo ""
echo -e "${YELLOW}Testing autoload...${NC}"
php -r "require 'vendor/autoload.php'; echo 'OK';" && echo -e "${GREEN}✓ Autoload works${NC}"

echo ""
echo -e "${GREEN}=== FIX COMPLETE ===${NC}"
echo ""
echo "Next steps:"
echo "  1. Try artisan command: php artisan migrate"
echo "  2. Check app logs for new errors"
echo ""
echo "If issue persists:"
echo "  1. Run: bash check-vendor-status.sh"
echo "  2. Check image build logs in GitHub Actions"
echo "  3. Force rebuild: git commit --allow-empty -m 'force rebuild' && git push"
