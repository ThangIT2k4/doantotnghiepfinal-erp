#!/bin/bash
# Script sửa lỗi HTTP 500 trên production
# Chạy: bash fix-500-commands.sh

echo "========================================"
echo "  SỬA LỖI HTTP 500 - PRODUCTION"
echo "========================================"
echo ""

# 1. Cài đặt dependencies
echo "[1/6] Cài đặt dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
if [ $? -eq 0 ]; then
    echo "  ✓ Đã cài đặt dependencies"
else
    echo "  ✗ Lỗi khi cài đặt dependencies"
    exit 1
fi

# 2. Clear cache
echo ""
echo "[2/6] Clear cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "  ✓ Đã clear cache"

# 3. Regenerate autoload
echo ""
echo "[3/6] Regenerate autoload..."
composer dump-autoload --optimize
echo "  ✓ Đã regenerate autoload"

# 4. Package discovery
echo ""
echo "[4/6] Package discovery..."
php artisan package:discover
echo "  ✓ Đã discover packages"

# 5. Cache lại
echo ""
echo "[5/6] Cache lại cho production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "  ✓ Đã cache lại"

# 6. Kiểm tra package
echo ""
echo "[6/6] Kiểm tra package dompdf..."
if [ -d "vendor/barryvdh/laravel-dompdf" ]; then
    echo "  ✓ Package barryvdh/laravel-dompdf đã được cài đặt"
else
    echo "  ✗ Package barryvdh/laravel-dompdf chưa được cài đặt"
    echo "    → Chạy lại: composer install --no-dev --optimize-autoloader"
fi

echo ""
echo "========================================"
echo "  HOÀN TẤT!"
echo "========================================"
echo ""
echo "Kiểm tra log nếu vẫn có lỗi:"
echo "  tail -n 100 storage/logs/laravel.log"
echo ""


