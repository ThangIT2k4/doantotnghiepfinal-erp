# ============================================================================
# CLEANUP BEFORE DEPLOY - Laravel to cPanel (PowerShell Version)
# ============================================================================
# 
# Script PowerShell để dọn dẹp cache trước khi deploy lên cPanel
# Tương tự cleanup-before-deploy.php nhưng cho Windows PowerShell
#
# CÁCH SỬ DỤNG:
# 1. Mở PowerShell tại thư mục project
# 2. Chạy: .\cleanup-before-deploy.ps1
# 3. Hoặc: powershell -ExecutionPolicy Bypass -File .\cleanup-before-deploy.ps1
#
# @author Laravel Deployment Helper
# @version 1.0
# ============================================================================

# Set error action
$ErrorActionPreference = "Continue"

# Colors for output
function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White",
        [switch]$NoNewline
    )
    
    $params = @{
        Object = $Message
        ForegroundColor = $Color
    }
    
    if ($NoNewline) {
        $params.Add('NoNewline', $true)
    }
    
    Write-Host @params
}

function Write-Header {
    param([string]$Message)
    Write-Host ""
    Write-ColorOutput "=" * 70 -Color Cyan
    Write-ColorOutput $Message -Color Cyan
    Write-ColorOutput "=" * 70 -Color Cyan
}

function Write-Success {
    param([string]$Message)
    Write-ColorOutput "✅ $Message" -Color Green
}

function Write-Error2 {
    param([string]$Message)
    Write-ColorOutput "❌ $Message" -Color Red
}

function Write-Warning2 {
    param([string]$Message)
    Write-ColorOutput "⚠️  $Message" -Color Yellow
}

function Write-Info {
    param([string]$Message)
    Write-ColorOutput "ℹ️  $Message" -Color Cyan
}

# Statistics
$stats = @{
    DeletedFiles = 0
    Errors = 0
    Warnings = 0
}

# ============================================================================
# HEADER
# ============================================================================
Clear-Host
Write-Header "🚀 Laravel Cleanup Script - Chuẩn bị Deploy lên cPanel"

$rootDir = Get-Location

# ============================================================================
# STEP 1: Clean Bootstrap Cache
# ============================================================================
Write-Header "📦 Bước 1: Dọn dẹp Bootstrap Cache"

$bootstrapCache = Join-Path $rootDir "bootstrap\cache"
$cacheFiles = @(
    "routes-v7.php",
    "config.php",
    "packages.php",
    "services.php"
)

foreach ($file in $cacheFiles) {
    $filePath = Join-Path $bootstrapCache $file
    if (Test-Path $filePath) {
        try {
            Remove-Item $filePath -Force
            Write-Success "Đã xóa: bootstrap\cache\$file"
            $stats.DeletedFiles++
        }
        catch {
            Write-Error2 "Không thể xóa: bootstrap\cache\$file"
            $stats.Errors++
        }
    }
    else {
        Write-Info "File không tồn tại (OK): bootstrap\cache\$file"
    }
}

# Delete all other .php files in bootstrap/cache
$allBootstrapCache = Get-ChildItem -Path $bootstrapCache -Filter "*.php" -ErrorAction SilentlyContinue
if ($allBootstrapCache) {
    foreach ($file in $allBootstrapCache) {
        if ($file.Name -ne ".gitignore") {
            try {
                Remove-Item $file.FullName -Force
                Write-Success "Đã xóa: bootstrap\cache\$($file.Name)"
                $stats.DeletedFiles++
            }
            catch {
                Write-Error2 "Không thể xóa: $($file.Name)"
            }
        }
    }
}

# ============================================================================
# STEP 2: Clean Compiled Views
# ============================================================================
Write-Header "🎨 Bước 2: Dọn dẹp Compiled Views"

$viewsDir = Join-Path $rootDir "storage\framework\views"
if (Test-Path $viewsDir) {
    $viewFiles = Get-ChildItem -Path $viewsDir -Filter "*.php" -ErrorAction SilentlyContinue
    if ($viewFiles) {
        $deletedViews = 0
        foreach ($file in $viewFiles) {
            if ($file.Name -ne ".gitignore") {
                try {
                    Remove-Item $file.FullName -Force
                    $deletedViews++
                    $stats.DeletedFiles++
                }
                catch {
                    # Silent fail for views
                }
            }
        }
        if ($deletedViews -gt 0) {
            Write-Success "Đã xóa $deletedViews file(s) từ storage\framework\views\"
        }
        else {
            Write-Info "Không có compiled views nào cần xóa"
        }
    }
    else {
        Write-Info "Thư mục views đã sạch"
    }
}
else {
    Write-Warning2 "Thư mục views không tồn tại: storage\framework\views\"
    $stats.Warnings++
}

# ============================================================================
# STEP 3: Clean Application Cache
# ============================================================================
Write-Header "💾 Bước 3: Dọn dẹp Application Cache"

$cacheDataDir = Join-Path $rootDir "storage\framework\cache\data"
if (Test-Path $cacheDataDir) {
    $cacheFiles = Get-ChildItem -Path $cacheDataDir -Recurse -File -ErrorAction SilentlyContinue
    $deletedCache = 0
    
    foreach ($file in $cacheFiles) {
        if ($file.Name -ne ".gitignore") {
            try {
                Remove-Item $file.FullName -Force
                $deletedCache++
                $stats.DeletedFiles++
            }
            catch {
                # Silent fail
            }
        }
    }
    
    if ($deletedCache -gt 0) {
        Write-Success "Đã xóa $deletedCache file(s) cache data"
    }
    else {
        Write-Info "Không có cache data nào cần xóa"
    }
}
else {
    Write-Info "Thư mục cache data không tồn tại (OK)"
}

# ============================================================================
# STEP 4: Clean Session Files
# ============================================================================
Write-Header "🔐 Bước 4: Dọn dẹp Session Files"

$sessionDir = Join-Path $rootDir "storage\framework\sessions"
if (Test-Path $sessionDir) {
    $sessionFiles = Get-ChildItem -Path $sessionDir -File -ErrorAction SilentlyContinue
    $deletedSessions = 0
    
    foreach ($file in $sessionFiles) {
        if ($file.Name -ne ".gitignore") {
            try {
                Remove-Item $file.FullName -Force
                $deletedSessions++
                $stats.DeletedFiles++
            }
            catch {
                # Silent fail
            }
        }
    }
    
    if ($deletedSessions -gt 0) {
        Write-Success "Đã xóa $deletedSessions session file(s)"
    }
    else {
        Write-Info "Không có session nào cần xóa"
    }
}
else {
    Write-Info "Thư mục sessions không tồn tại (OK)"
}

# ============================================================================
# STEP 5: Verify Critical Directories
# ============================================================================
Write-Header "🔍 Bước 5: Kiểm tra cấu trúc thư mục"

$requiredDirs = @(
    "bootstrap\cache",
    "storage\app",
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs"
)

foreach ($dir in $requiredDirs) {
    $fullPath = Join-Path $rootDir $dir
    if (Test-Path $fullPath) {
        Write-Success "Thư mục tồn tại: $dir"
    }
    else {
        Write-Error2 "Thư mục không tồn tại: $dir"
        $stats.Errors++
    }
}

# ============================================================================
# STEP 6: Check .env Configuration
# ============================================================================
Write-Header "⚙️  Bước 6: Kiểm tra cấu hình .env"

$envFile = Join-Path $rootDir ".env"
if (Test-Path $envFile) {
    Write-Success "File .env tồn tại"
    
    $envContent = Get-Content $envFile -Raw
    
    # Check APP_KEY
    if ($envContent -match 'APP_KEY=base64:[A-Za-z0-9+/=]+') {
        Write-Success "APP_KEY đã được generate"
    }
    else {
        Write-Warning2 "APP_KEY chưa được generate - chạy: php artisan key:generate"
        $stats.Warnings++
    }
    
    # Check APP_ENV
    if ($envContent -match 'APP_ENV=production') {
        Write-Info "APP_ENV=production (Sẵn sàng cho production)"
    }
    else {
        Write-Warning2 "APP_ENV không phải production (OK cho staging/test)"
    }
    
    # Check APP_DEBUG
    if ($envContent -match 'APP_DEBUG=false') {
        Write-Info "APP_DEBUG=false (Đúng cho production)"
    }
    else {
        Write-Warning2 "APP_DEBUG=true (Nên đổi thành false trên production)"
        $stats.Warnings++
    }
}
else {
    Write-Error2 "File .env không tồn tại!"
    Write-Info "Copy .env.example thành .env và cấu hình"
    $stats.Errors++
}

# ============================================================================
# SUMMARY
# ============================================================================
Write-Header "📊 Tổng kết"

if ($stats.Errors -eq 0) {
    Write-Success "✅ Cleanup hoàn tất thành công!"
    Write-Info "📁 Đã xóa: $($stats.DeletedFiles) file(s)"
    if ($stats.Warnings -gt 0) {
        Write-Warning2 "⚠️  Cảnh báo: $($stats.Warnings) vấn đề cần chú ý"
    }
}
else {
    Write-Error2 "❌ Có $($stats.Errors) lỗi xảy ra"
    Write-Info "📁 Đã xóa: $($stats.DeletedFiles) file(s)"
    Write-Warning2 "⚠️  Cảnh báo: $($stats.Warnings) vấn đề"
}

# ============================================================================
# DEPLOYMENT CHECKLIST
# ============================================================================
Write-Header "📋 Checklist Deploy lên cPanel"

$checklist = @(
    "✅ Đã chạy cleanup script này",
    "✅ File .env đã cấu hình đúng",
    "✅ APP_ENV=production, APP_DEBUG=false",
    "✅ Không có file cache trong bootstrap\cache\",
    "✅ Database credentials đúng trên server",
    "✅ APP_URL khớp với domain/subdomain",
    "⏭️  Zip toàn bộ project (hoặc upload qua FTP)",
    "⏭️  Upload lên cPanel",
    "⏭️  Extract trên server",
    "⏭️  Cấu hình Document Root → public\",
    "⏭️  Set permissions: storage\ và bootstrap\cache\ → 755",
    "⏭️  Test website"
)

foreach ($item in $checklist) {
    Write-Info $item
}

# ============================================================================
# IMPORTANT NOTES
# ============================================================================
Write-Header "⚠️  Lưu ý quan trọng"

Write-Warning2 "❌ KHÔNG chạy các lệnh sau trên cPanel:"
Write-ColorOutput "   - php artisan route:cache" -Color Red
Write-ColorOutput "   - php artisan config:cache" -Color Red
Write-ColorOutput "   - php artisan view:cache" -Color Red

Write-Host ""
Write-Info "✅ Chỉ chạy trên local khi develop:"
Write-ColorOutput "   - php artisan route:clear" -Color Green
Write-ColorOutput "   - php artisan config:clear" -Color Green
Write-ColorOutput "   - php artisan cache:clear" -Color Green

# ============================================================================
# NEXT STEPS
# ============================================================================
Write-Header "🎯 Bước tiếp theo"

Write-Info "1️⃣  Kiểm tra lại các cảnh báo (nếu có)"
Write-Info "2️⃣  Zip/Upload project lên cPanel"
Write-Info "3️⃣  Nếu gặp lỗi 405, upload file: fix-405-route-error.php"
Write-Info "4️⃣  Test website kỹ lưỡng"

Write-Host ""
Write-ColorOutput "🎉 Cleanup hoàn tất! Project sẵn sàng deploy." -Color Green
Write-Host ""

# Pause to see results
Read-Host "Nhấn Enter để đóng..."

