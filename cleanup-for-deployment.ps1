# Script dọn dẹp file trước khi deploy (PowerShell)
# Chạy: .\cleanup-for-deployment.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  DỌN DẸP FILE TRƯỚC KHI DEPLOY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Xác nhận trước khi xóa
$confirm = Read-Host "Bạn có chắc chắn muốn dọn dẹp? (yes/no)"
if ($confirm -ne "yes") {
    Write-Host "Đã hủy." -ForegroundColor Yellow
    exit
}

Write-Host "Bắt đầu dọn dẹp..." -ForegroundColor Green
Write-Host ""

# 1. Xóa thư mục migrations cũ
if (Test-Path "database\migrations_1") {
    Write-Host "[1/12] Xóa database/migrations_1..." -ForegroundColor Yellow
    Remove-Item -Recurse -Force "database\migrations_1"
    Write-Host "  ✓ Đã xóa" -ForegroundColor Green
}

# 2. Xóa file SQL backup
if (Test-Path "database\sql") {
    Write-Host "[2/12] Xóa database/sql/*.sql..." -ForegroundColor Yellow
    Get-ChildItem "database\sql\*.sql" | Remove-Item -Force
    Write-Host "  ✓ Đã xóa" -ForegroundColor Green
}

# 3. Xóa file SQL geo
Write-Host "[3/12] Xóa hanoi_*.sql..." -ForegroundColor Yellow
Get-ChildItem "hanoi_*.sql" -ErrorAction SilentlyContinue | Remove-Item -Force
Write-Host "  ✓ Đã xóa" -ForegroundColor Green

# 4. Xóa file test trong public
Write-Host "[4/12] Xóa file test trong public/..." -ForegroundColor Yellow
Get-ChildItem "public\test*.html" -ErrorAction SilentlyContinue | Remove-Item -Force
Get-ChildItem "public\debug.html" -ErrorAction SilentlyContinue | Remove-Item -Force
Get-ChildItem "public\test-style.css" -ErrorAction SilentlyContinue | Remove-Item -Force
Write-Host "  ✓ Đã xóa" -ForegroundColor Green

# 5. Xóa file test trong root
Write-Host "[5/12] Xóa file test trong root..." -ForegroundColor Yellow
Get-ChildItem "test-*.php" -ErrorAction SilentlyContinue | Remove-Item -Force
Write-Host "  ✓ Đã xóa" -ForegroundColor Green

# 6. Xóa controller/command test
Write-Host "[6/12] Xóa controller/command test..." -ForegroundColor Yellow
Get-ChildItem "app\Http\Controllers\Test*.php" -ErrorAction SilentlyContinue | Remove-Item -Force
Get-ChildItem "app\Console\Commands\Test*.php" -ErrorAction SilentlyContinue | Remove-Item -Force
Write-Host "  ✓ Đã xóa" -ForegroundColor Green

# 7. Xóa thư mục Examples
if (Test-Path "app\Examples") {
    Write-Host "[7/12] Xóa app/Examples..." -ForegroundColor Yellow
    Remove-Item -Recurse -Force "app\Examples"
    Write-Host "  ✓ Đã xóa" -ForegroundColor Green
}

# 8. Xóa thư mục backup
if (Test-Path "backup") {
    Write-Host "[8/12] Xóa backup/..." -ForegroundColor Yellow
    Remove-Item -Recurse -Force "backup"
    Write-Host "  ✓ Đã xóa" -ForegroundColor Green
}

# 9. Xóa database SQLite
if (Test-Path "database\database.sqlite") {
    Write-Host "[9/12] Xóa database/database.sqlite..." -ForegroundColor Yellow
    Remove-Item -Force "database\database.sqlite"
    Write-Host "  ✓ Đã xóa" -ForegroundColor Green
}

# 10. Xóa file download.jpg
if (Test-Path "database\migrations\download.jpg") {
    Write-Host "[10/12] Xóa database/migrations/download.jpg..." -ForegroundColor Yellow
    Remove-Item -Force "database\migrations\download.jpg"
    Write-Host "  ✓ Đã xóa" -ForegroundColor Green
}

# 11. Xóa script helper
Write-Host "[11/12] Xóa script helper..." -ForegroundColor Yellow
$scripts = @(
    "artisan-key-generator.php",
    "clear-cache.php",
    "create-storage-link.php",
    "backup_database.php",
    "clear_database.php",
    "check_circular_references.php",
    "build_hanoi_geo_sql.php",
    "import_hanoi_2cap.php",
    "composer-installer.php",
    "setup.ps1",
    "setup.sh",
    "update-manager-to-staff.ps1"
)
foreach ($script in $scripts) {
    if (Test-Path $script) {
        Remove-Item -Force $script
    }
}
Write-Host "  ✓ Đã xóa" -ForegroundColor Green

# 12. Xóa log files
Write-Host "[12/12] Xóa storage/logs/*.log..." -ForegroundColor Yellow
if (Test-Path "storage\logs") {
    Get-ChildItem "storage\logs\*.log" -ErrorAction SilentlyContinue | Remove-Item -Force
}
Write-Host "  ✓ Đã xóa" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  DỌN DẸP HOÀN TẤT!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Bước tiếp theo:" -ForegroundColor Yellow
Write-Host "  1. Chạy: composer install --no-dev --optimize-autoloader" -ForegroundColor White
Write-Host "  2. Chạy: php artisan config:cache" -ForegroundColor White
Write-Host "  3. Chạy: php artisan route:cache" -ForegroundColor White
Write-Host "  4. Chạy: php artisan view:cache" -ForegroundColor White
Write-Host "  5. Kiểm tra .env production" -ForegroundColor White
Write-Host ""

