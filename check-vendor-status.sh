#!/bin/bash
# Quick vendor status checker — run này trên server khi gặp autoload error

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== VENDOR STATUS CHECK ===${NC}\n"

# 1. Check nếu vendor folder tồn tại
if [ ! -d "vendor" ]; then
    echo -e "${RED}✗ vendor/ NOT FOUND${NC}"
    exit 1
fi
echo -e "${GREEN}✓ vendor/ exists${NC}"

# 2. Check composer autoload
if [ ! -f "vendor/composer/autoload_real.php" ]; then
    echo -e "${RED}✗ autoload_real.php NOT FOUND${NC}"
    ls -la vendor/composer/ 2>/dev/null || echo "composer/ folder empty"
    exit 1
fi
echo -e "${GREEN}✓ autoload_real.php exists${NC}"

# 3. Check thecodingmachine/safe
if [ ! -d "vendor/thecodingmachine/safe" ]; then
    echo -e "${RED}✗ thecodingmachine/safe package NOT FOUND${NC}"
    echo "Available packages in vendor/thecodingmachine/:"
    ls -la vendor/thecodingmachine/ 2>/dev/null | head -20 || echo "(folder missing)"
    exit 1
fi
echo -e "${GREEN}✓ thecodingmachine/safe exists${NC}"

# 4. Check special_cases.php
if [ ! -f "vendor/thecodingmachine/safe/lib/special_cases.php" ]; then
    echo -e "${RED}✗ special_cases.php NOT FOUND${NC}"
    echo "Files in vendor/thecodingmachine/safe/lib/:"
    ls -la vendor/thecodingmachine/safe/lib/ 2>/dev/null || echo "(lib folder missing)"
    exit 1
fi
echo -e "${GREEN}✓ special_cases.php exists${NC}"

# 5. Check composer.lock consistency
echo ""
echo -e "${YELLOW}Checking composer.lock...${NC}"
if grep -q "thecodingmachine/safe" composer.lock; then
    echo -e "${GREEN}✓ thecodingmachine/safe in composer.lock${NC}"
    VERSION=$(grep -A 5 '"name": "thecodingmachine/safe"' composer.lock | grep '"version"' | head -1 | cut -d'"' -f4)
    echo "  Version: $VERSION"
else
    echo -e "${YELLOW}⚠ thecodingmachine/safe NOT in composer.lock${NC}"
fi

# 6. Test autoload
echo ""
echo -e "${YELLOW}Testing autoload...${NC}"
php -r "require 'vendor/autoload.php'; echo 'OK';" && echo -e "${GREEN}✓ Autoload works${NC}" || (echo -e "${RED}✗ Autoload FAILED${NC}" && exit 1)

# 7. Count packages
PACKAGE_COUNT=$(find vendor -maxdepth 2 -type d -mindepth 2 | wc -l)
echo ""
echo -e "${GREEN}✓ Total packages: $PACKAGE_COUNT${NC}"

echo ""
echo -e "${GREEN}=== ALL CHECKS PASSED ===${NC}"
echo ""
echo "If you still see autoload errors, run:"
echo "  composer install --no-dev --optimize-autoloader"
echo "  composer dump-autoload -o"
