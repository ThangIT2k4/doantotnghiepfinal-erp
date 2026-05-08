#!/bin/bash
# Script dọn dẹp file trước khi deploy (Bash)
# Chạy: chmod +x cleanup-for-deployment.sh && ./cleanup-for-deployment.sh

echo "========================================"
echo "  DỌN DẸP FILE TRƯỚC KHI DEPLOY"
echo "========================================"
echo ""

# Xác nhận trước khi xóa
read -p "Bạn có chắc chắn muốn dọn dẹp? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Đã hủy."
    exit 1
fi

echo "Bắt đầu dọn dẹp..."
echo ""

# 1. Xóa thư mục migrations cũ
if [ -d "database/migrations_1" ]; then
    echo "[1/12] Xóa database/migrations_1..."
    rm -rf database/migrations_1
    echo "  ✓ Đã xóa"
fi

# 2. Xóa file SQL backup
if [ -d "database/sql" ]; then
    echo "[2/12] Xóa database/sql/*.sql..."
    rm -f database/sql/*.sql
    echo "  ✓ Đã xóa"
fi

# 3. Xóa file SQL geo
echo "[3/12] Xóa hanoi_*.sql..."
rm -f hanoi_*.sql
echo "  ✓ Đã xóa"

# 4. Xóa file test trong public
echo "[4/12] Xóa file test trong public/..."
rm -f public/test*.html public/debug.html public/test-style.css
echo "  ✓ Đã xóa"

# 5. Xóa file test trong root
echo "[5/12] Xóa file test trong root..."
rm -f test-*.php
echo "  ✓ Đã xóa"

# 6. Xóa controller/command test
echo "[6/12] Xóa controller/command test..."
rm -f app/Http/Controllers/Test*.php
rm -f app/Console/Commands/Test*.php
echo "  ✓ Đã xóa"

# 7. Xóa thư mục Examples
if [ -d "app/Examples" ]; then
    echo "[7/12] Xóa app/Examples..."
    rm -rf app/Examples
    echo "  ✓ Đã xóa"
fi

# 8. Xóa thư mục backup
if [ -d "backup" ]; then
    echo "[8/12] Xóa backup/..."
    rm -rf backup
    echo "  ✓ Đã xóa"
fi

# 9. Xóa database SQLite
if [ -f "database/database.sqlite" ]; then
    echo "[9/12] Xóa database/database.sqlite..."
    rm -f database/database.sqlite
    echo "  ✓ Đã xóa"
fi

# 10. Xóa file download.jpg
if [ -f "database/migrations/download.jpg" ]; then
    echo "[10/12] Xóa database/migrations/download.jpg..."
    rm -f database/migrations/download.jpg
    echo "  ✓ Đã xóa"
fi

# 11. Xóa script helper
echo "[11/12] Xóa script helper..."
rm -f artisan-key-generator.php
rm -f clear-cache.php
rm -f create-storage-link.php
rm -f backup_database.php
rm -f clear_database.php
rm -f check_circular_references.php
rm -f build_hanoi_geo_sql.php
rm -f import_hanoi_2cap.php
rm -f composer-installer.php
rm -f setup.ps1
rm -f setup.sh
rm -f update-manager-to-staff.ps1
echo "  ✓ Đã xóa"

# 12. Xóa log files
echo "[12/12] Xóa storage/logs/*.log..."
if [ -d "storage/logs" ]; then
    rm -f storage/logs/*.log
fi
echo "  ✓ Đã xóa"

echo ""
echo "========================================"
echo "  DỌN DẸP HOÀN TẤT!"
echo "========================================"
echo ""
echo "Bước tiếp theo:"
echo "  1. Chạy: composer install --no-dev --optimize-autoloader"
echo "  2. Chạy: php artisan config:cache"
echo "  3. Chạy: php artisan route:cache"
echo "  4. Chạy: php artisan view:cache"
echo "  5. Kiểm tra .env production"
echo ""

